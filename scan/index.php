<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QRCode Scanner</title>
    <style>
        button {
            margin-top: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div id="qr-reader" style="width:100%; height:auto"></div>
    <div id="qr-reader-results"></div>
    <script src="qrcode.min.js"></script>
    <script>
        function docReady(fn) {
            // see if DOM is already available
            if (document.readyState === "complete" ||
                document.readyState === "interactive") {
                // call on next available tick
                setTimeout(fn, 1);
            } else {
                document.addEventListener("DOMContentLoaded", fn);
            }
        }

        function getAllUrlParams(e) {
            var t = e ? e.split("?")[1] : window.location.search.slice(1),
                a = {};
            if (t)
                for (var n = (t = t.split("#")[0]).split("&"), o = 0; o < n.length; o++) {
                    var i = n[o].split("="),
                        r = void 0,
                        d = i[0].replace(/\[\d*\]/, function (e) {
                            return (r = e.slice(1, -1)), "";
                        }),
                        s = void 0 === i[1] || i[1];
                    a[(d = d.toLowerCase())]
                        ? ("string" == typeof a[d] && (a[d] = [a[d]]),
                            void 0 === r ? a[d].push(s) : (a[d][r] = s))
                        : (a[d] = s);
                }
            return a;
        }

        docReady(function() {
            var resultContainer = document.getElementById('qr-reader-results');
            var lastResult, countResults = 0;
            function onScanSuccess(decodedText, decodedResult) {
                if (decodedText !== lastResult) {
                    ++countResults;
                    lastResult = decodedText;
                    if(getAllUrlParams().back != undefined){
                        window.location = unescape(getAllUrlParams().back) + escape(decodedText);
                    }else{
                        if(decodedText.startsWith('http')){
                            window.location = decodedText;
                        }else{
                            alert(decodedText);
                        }
                    }
                }
            }

            var html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", {
                    fps: 10,
                    qrbox: 250
                });
            html5QrcodeScanner.render(onScanSuccess);
        });
    </script>
</body>

</html>