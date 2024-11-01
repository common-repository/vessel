/**
 * Created by Aaron Allen on 6/13/2018.
 */

var insightsUrl;

var queue = [];

var interval = setInterval(sendData, 8000);

var startTimes = {};

function sendData() {
    // if there is anything in the queue, send it
    if (queue.length === 0) return;

    var req = new XMLHttpRequest();
    req.open('POST', insightsUrl);
    req.setRequestHeader('Content-type', 'application/json');
    req.onerror = function (err) {
        //console.log(err);
    };
    req.send(JSON.stringify({
        //campaignId: campaignId,
        data: queue,
        code: 'map'
    }));

    queue = [];
}

onmessage = function (ev) {
    if (ev.data.type === 'init') {
        //var campaignId = ev.data.campaignId;
        // mark start time for viewing this campaign
        //startTimes[campaignId] = Date.now();

        if (!insightsUrl) {
            insightsUrl = ev.data.insightsUrl;
        }

        return;
    }

    if (ev.data.type === 'insight') {
        // record start time of a campaign view
        var packet = ev.data.content;
        if (packet.type === 'campaignView') {
            startTimes[packet.id] = Date.now();

            if (!packet.incrementViews) return;
        }
        
        queue.push(ev.data.content);
        return;
    }

    if (ev.data.type === 'endCampaignView') {
        campaignId = ev.data.id;
        var startTime = startTimes[campaignId];

        if (startTime) {
            queue.push({
                campaignId:       campaignId,
                type:     'campaignViewEnded',
                duration: Date.now() - startTime
            });
        }

        return;
    }

    if (ev.data.type === 'closing') {
        // send final packet before page closes
        for (var id in startTimes) {
            if (!startTimes.hasOwnProperty(id)) continue;

            startTime = startTimes[id];
            queue.push({
                campaignId:       id,
                type:     'campaignViewEnded',
                duration: Date.now() - startTime
            });
        }
        startTimes = {};

        sendData();
    }
};