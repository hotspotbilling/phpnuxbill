<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>QR Code Scanner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        #container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        #qr-reader {
            width: 100%;
            height: auto;
            margin-bottom: 20px;
        }
        #qr-reader-results {
            padding: 10px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            margin-top: 30px;
            margin-bottom: 30px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div id="container">
        <h2>QR Code Scanner</h2>
        <div id="qr-reader"></div>
        <div id="qr-reader-results"></div>
        <button id="camera-button">Open Camera</button>
    </div>
    <script src="qrcode.min.js"></script>
    <script>
        function docReady(fn) {
            // lihat apakah DOM sudah tersedia
            if (document.readyState === "complete" ||
                document.readyState === "interactive") {
                // panggil di detik berikutnya yang tersedia
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
            var html5QrcodeScanner;
            var isCameraOpen = false;

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

            function toggleCamera() {
                if (isCameraOpen) {
                    html5QrcodeScanner.clear();
                    document.getElementById('camera-button').textContent = "Open Camera";
                    isCameraOpen = false;
                } else {
                    html5QrcodeScanner = new Html5QrcodeScanner(
                        "qr-reader", {
                            fps: 10,
                            qrbox: 250
                        });
                    html5QrcodeScanner.render(onScanSuccess);
                    document.getElementById('camera-button').textContent = "Close Camera";
                    isCameraOpen = true;
                }
            }

            document.getElementById('camera-button').addEventListener('click', toggleCamera);
        });
    </script>
</body>

</html>
