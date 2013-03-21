#!/usr/bin/env python2
# This can be replaced with the built-in json module, if desired.
import logging
import simplejson
import json
from xml.utils.iso8601 import parse
import datetime
import MySQLdb
from datetime import datetime
import gevent
from gevent.pool import Pool
from gevent import monkey; gevent.monkey.patch_all()
import zmq.green as zmq
import zlib
import pylibmc
import operator
import sys

# The maximum number of greenlet workers in the greenlet pool. This is not one
# per processor, a decent machine can support hundreds or thousands of greenlets.
# I recommend setting this to the maximum number of connections your database
# backend can accept, if you must open one connection per save op.
MAX_NUM_POOL_WORKERS = 11
DEBUG = False
REGIONS = 'regions.json' #set this to a file containing JSON of regions to filter against

# data stored, not implemented
SELL    = True
BUY     = False
HISTORY = False

## todo: customizable key format
## todo: use of mysql tables for storage / backup

class Printer():
    """
    Print things to stdout on one line dynamically
    """
 
    def __init__(self,data):
 
        sys.stdout.write("\r\x1b[K"+data.__str__())
        sys.stdout.flush()

mc = pylibmc.Client(["127.0.0.1"], binary=True, behaviors={"tcp_nodelay": True, "ketama": True})



def main():
    """
    The main flow of the application.
    """

    global f
    global s
    context = zmq.Context()
    subscriber = context.socket(zmq.SUB)
    # Connect to the first publicly available relay.
    subscriber.connect('tcp://relay-us-east-1.eve-emdr.com:8050')

    # Disable filtering.
    subscriber.setsockopt(zmq.SUBSCRIBE, "")

    # We use a greenlet pool to cap the number of workers at a reasonable level.
    greenlet_pool = Pool(size=MAX_NUM_POOL_WORKERS) 
    
    print("Consumer daemon started, waiting for jobs...")
    print("Worker pool size: %d" % greenlet_pool.size)
    i = f = s = 0
    while True:
        # Since subscriber.recv() blocks when no messages are available,
        # this loop stays under control. If something is available and the
        # greenlet pool has greenlets available for use, work gets done.
        greenlet_pool.spawn(worker, subscriber.recv())
        output = "%d/%d orders processed, %d skipped due to up-to-date cache" % (f,i,s)
        Printer(output)
        i += 1
    
def worker(job_json):
    """
    For every incoming message, this worker function is called. Be extremely
    careful not to do anything CPU-intensive here, or you will see blocking.
    Sockets are async under gevent, so those are fair game.
    """
    
    '''
    todo:   look into putting it into mysql, loading mysql into memcache
            look into logging to files per type id
    '''
    global f;
    global s;
    
    if REGIONS is not False:
        json_data = open(REGIONS)
        regionDict   = json.load(json_data)
        json_data.close()
    else:
        pass
    
    # Receive raw market JSON strings.
    market_json = zlib.decompress(job_json);

    # Un-serialize the JSON data to a Python dict.
    market_data = simplejson.loads(market_json);

    # Gather some useful information
    name = market_data.get('generator');
    name = name['name'];
    resultType = market_data.get('resultType');
    rowsets = market_data.get('rowsets')[0];
    typeID = rowsets['typeID'];
    columns = market_data.get('columns');
    
    # Convert str time to int
    currentTime = parse(market_data.get('currentTime'));
    generatedAt = parse(rowsets['generatedAt']);
    
    numberOfSellItems = 0;
    sellPrice = {}
    if (resultType == 'orders'):
        if (REGIONS == False or (REGIONS != False and rowsets['regionID'] in regionDict.values())):
            if (DEBUG):
                print "\n\n\n\n======== New record ========";
            cached = mc.get('emdr-region:'+str(rowsets['regionID'])+'-typeID:'+str(typeID));
            
            # If data has been cached for this item, check the dates. If dates match, skip
            if (cached != None):
                #if we have data in cache, split info into list
                cachePieces = simplejson.loads(cached);
                
                # parse date
                cachedate = cachePieces[3];
                if (DEBUG):
                        print "\nCached data found!\n\tNew date: "+str(datetime.fromtimestamp(generatedAt))+"\n\tCached date: "+ str(datetime.fromtimestamp(cachedate));
                if (generatedAt < cachedate):
                    s += 1
                    if (DEBUG):
                        print "\t\tSKIPPING";
                    return '';

            for row in rowsets['rows']:
                order = dict(zip(columns, row))
                
                if (order['bid'] == False):
                    if (DEBUG):
                        print "Found sell order for "+str(order['price']) + "; vol: "+str(order['volRemaining']);
                    if (sellPrice.get(order['price']) != None):
                        sellPrice[order['price']] += order['volRemaining'];
                    else:
                        sellPrice[order['price']] = order['volRemaining'];
                    numberOfSellItems += order['volRemaining'];
            if (DEBUG):    
                print "Total volume on market: ",numberOfSellItems
            
            if (numberOfSellItems > 0):
                prices = sorted(sellPrice.items(), key=lambda x: x[0]);
                fivePercentOfTotal = max(int(numberOfSellItems*0.05),1);
                fivePercentPrice=0;
                bought=0;
                boughtPrice=0;
                if (DEBUG):
                    print "Prices (sorted):\n",prices
                    print "Start buying process!"
                while (bought < fivePercentOfTotal):
                    pop = prices.pop(0)
                    fivePercentPrice = pop[0]
                    if (DEBUG):
                        print "\tBought: ",bought,"/",fivePercentOfTotal
                        print "\t\tNext pop: ",fivePercentPrice," ISK, vol: ",pop[1]
                    
                    if (fivePercentOfTotal > ( bought + sellPrice[fivePercentPrice])):
                        boughtPrice += sellPrice[fivePercentPrice]*fivePercentPrice;
                        bought += sellPrice[fivePercentPrice];
                        if (DEBUG):
                            print "\t\tHave not met goal. Bought:",bought
                    else:
                        diff = fivePercentOfTotal - bought;
                        boughtPrice += fivePercentPrice*diff;
                        bought = fivePercentOfTotal;
                        if (DEBUG):
                            print "\t\tGoal met. Bought:",bought
                
                fiveAverageSellPrice = boughtPrice/bought;
                if (DEBUG):
                    print "Average selling price (first 5% of volume):",fiveAverageSellPrice
                values = [
                    fiveAverageSellPrice,
                    numberOfSellItems,
                    fivePercentOfTotal,
                    generatedAt]
                mc.set('emdr-region:'+str(rowsets['regionID'])+'-typeID:'+str(typeID), simplejson.dumps(values));
                if (DEBUG):
                    print 'SUCCESS: emdr-region:'+str(rowsets['regionID'])+'-typeID:'+str(typeID)
                f += 1
           

if __name__ == '__main__':
    main()