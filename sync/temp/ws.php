<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>


		<script>


			document.addEventListener("DOMContentLoaded", function () {


				function connect() {

					let ws = new WebSocket("wss://s4.olkha.com/socket/");
					ws.onopen = function () { 
						console.log('connected');
						ws.send(JSON.stringify({
							data: 'mockup data'
									//.... some message the I must send when I connect ....
						}));
					};

					ws.onmessage = function (e) {
						console.log('e.data:', e.data);
					};

					ws.onclose = function (e) {
						console.log('Socket is closed. Reconnect will be attempted in 1 second.', e.reason);
						setTimeout(function () {
							connect();
						}, 1000);
					};

					ws.onerror = function (err) {
						console.error('Socket encountered error: ', err.message, 'Closing socket');
						ws.close();
					};
				}

				connect();



			});

		</script>

    </body>
</html>
