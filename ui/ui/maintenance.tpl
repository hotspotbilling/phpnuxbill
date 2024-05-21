<!DOCTYPE html>
<html>

<head>
    <title>{Lang::T('Site is down for maintenance')}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            font-family: 'Noto Sans', sans-serif;
            color: #616161;
            background-color: #eeeeee;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            max-width: 1024px;
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        .box {
            width: 100%;
            background: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 6px 18px 18px rgba(0, 0, 0, 0.08), -6px 18px 18px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
            text-align: center;
        }

        .animation {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }

        h1 {
            font-size: 32px;
            font-weight: 400;
            text-transform: uppercase;
            margin: 0;
        }

        p {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        a {
            color: #f6921e;
            font-weight: bold;
            text-decoration: none;
            margin-left: 5px;
        }

        .one,
        .two,
        .three {
            display: block;
            float: left;
        }

        .one {
            background: url('data:image/svg+xml,%3Csvg%20version%3D%221.1%22%0A%09%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20xmlns%3Axlink%3D%22http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink%22%20xmlns%3Aa%3D%22http%3A%2F%2Fns.adobe.com%2FAdobeSVGViewerExtensions%2F3.0%2F%22%0A%09%20x%3D%220px%22%20y%3D%220px%22%20width%3D%2281px%22%20height%3D%2280.5px%22%20viewBox%3D%220%200%2081%2080.5%22%20style%3D%22overflow%3Ascroll%3Benable-background%3Anew%200%200%2081%2080.5%3B%22%0A%09%20xml%3Aspace%3D%22preserve%22%3E%0A%3Cstyle%20type%3D%22text%2Fcss%22%3E%0A%09.st0%7Bfill%3A%23383838%3B%7D%0A%3C%2Fstyle%3E%0A%3Cdefs%3E%0A%3C%2Fdefs%3E%0A%3Cpath%20class%3D%22st0%22%20d%3D%22M30.3%2C68.2c1.2%2C0.2%2C2.3%2C0.9%2C3.8%2C1.2c1.6%2C0.3%2C2.7%2C0.6%2C4%2C0.4l4.9%2C9.6c0.6%2C0.9%2C1.4%2C1.1%2C2.3%2C0.9l15.3-4.9%0A%09c0.5-0.3%2C1-1%2C0.9-2.3l-1.8-10.6c2-1.6%2C3.6-3.7%2C5.3-5.8l10.5%2C0.6c1.1%2C0.6%2C2.1-0.4%2C2.3-1.1L81%2C40.7c0.2-0.8-0.4-2.1-1.1-2.3l-10.2-3.8%0A%09c-0.3-2.5-1.4-4.8-2.5-7.5l5.9-8.5c0.6-1.1%2C0.4-1.9-0.2-2.9l-12-10.7c-0.3-0.5-1.6-0.3-2.5%2C0.3l-8%2C6.9c-1.2-0.2-2.3-0.9-3.8-1.2%0A%09c-1.6-0.3-2.7-0.6-4-0.4L37.7%2C1c-0.6-0.9-1.4-1.1-2.3-0.9L20.1%2C5c-0.5%2C0.3-1%2C1-0.9%2C2.3l1.8%2C10.6c-2%2C1.6-3.6%2C3.7-5.3%2C5.8L5.3%2C23%0A%09c-0.8-0.2-1.7%2C0.4-2%2C1.6L0%2C40.2c-0.2%2C0.8%2C0.4%2C2.1%2C1.1%2C2.3l9.8%2C3.7c0.7%2C2.6%2C1.4%2C5.2%2C2.5%2C7.5l-6%2C8.9c-0.6%2C0.7-0.4%2C2%2C0.3%2C2.5l12%2C10.7%0A%09c0.7%2C0.5%2C1.9%2C0.8%2C2.4%2C0.1L30.3%2C68.2z%20M26.7%2C37.3c1.6-7.4%2C9.1-12.3%2C16.5-10.8S55.6%2C35.7%2C54%2C43.1c-1.6%2C7.4-9.1%2C12.3-16.5%2C10.7%0A%09C30.1%2C52.3%2C25.1%2C44.7%2C26.7%2C37.3L26.7%2C37.3z%22%2F%3E%0A%3C%2Fsvg%3E');
            width: 80px;
            height: 80px;
            background-size: 100% 100%;
            background-repeat: no-repeat;
            margin-top: -10px;
            margin-right: 8px;
        }

        .two {
            background: url('data:image/svg+xml,%3Csvg%20version%3D%221.1%22%0A%09%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20xmlns%3Axlink%3D%22http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink%22%20xmlns%3Aa%3D%22http%3A%2F%2Fns.adobe.com%2FAdobeSVGViewerExtensions%2F3.0%2F%22%0A%09%20x%3D%220px%22%20y%3D%220px%22%20width%3D%22103px%22%20height%3D%22103.7px%22%20viewBox%3D%220%200%20103%20103.7%22%0A%09%20style%3D%22overflow%3Ascroll%3Benable-background%3Anew%200%200%20103%20103.7%3B%22%20xml%3Aspace%3D%22preserve%22%3E%0A%3Cstyle%20type%3D%22text%2Fcss%22%3E%0A%09.st0%7Bfill%3A%23F6921E%3B%7D%0A%3C%2Fstyle%3E%0A%3Cdefs%3E%0A%3C%2Fdefs%3E%0A%3Cpath%20class%3D%22st0%22%20d%3D%22M87.3%2C64.8c0.3-1.5%2C1.1-2.9%2C1.6-4.9c0.4-2%2C0.7-3.5%2C0.5-5.1l12.3-6.3c1.2-0.8%2C1.4-1.8%2C1.1-2.9l-6.3-19.6%0A%09c-0.4-0.6-1.3-1.3-2.9-1.1l-13.5%2C2.3c-2.1-2.5-4.7-4.7-7.4-6.8l0.8-13.4C74.3%2C5.8%2C73%2C4.5%2C72%2C4.3L52.1%2C0c-1-0.2-2.7%2C0.5-2.9%2C1.5%0A%09l-4.8%2C13c-3.2%2C0.4-6.1%2C1.8-9.5%2C3.2l-10.9-7.5c-1.4-0.8-2.5-0.5-3.7%2C0.3L6.5%2C25.8c-0.6%2C0.4-0.4%2C2%2C0.4%2C3.2l8.8%2C10.2%0A%09c-0.3%2C1.5-1.1%2C2.9-1.5%2C4.9c-0.4%2C2-0.7%2C3.5-0.6%2C5.1L1.2%2C55.4c-1.2%2C0.8-1.4%2C1.8-1.1%2C2.9l6.3%2C19.6c0.4%2C0.6%2C1.3%2C1.3%2C2.9%2C1.1l13.5-2.3%0A%09c2.1%2C2.5%2C4.7%2C4.7%2C7.4%2C6.8l-0.8%2C13.4c-0.2%2C1%2C0.6%2C2.2%2C2.1%2C2.5l20%2C4.2c1%2C0.2%2C2.7-0.5%2C2.9-1.5l4.7-12.6c3.3-0.9%2C6.6-1.7%2C9.5-3.2L80.1%2C94%0A%09c0.9%2C0.7%2C2.5%2C0.5%2C3.2-0.4L97%2C78.3c0.7-0.9%2C1-2.4%2C0.1-3.1L87.3%2C64.8z%20M47.8%2C69.5C38.3%2C67.5%2C32%2C57.8%2C34%2C48.3%0A%09c2-9.5%2C11.7-15.8%2C21.2-13.8c9.5%2C2%2C15.7%2C11.7%2C13.7%2C21.2C66.9%2C65.2%2C57.3%2C71.5%2C47.8%2C69.5L47.8%2C69.5z%22%2F%3E%0A%3C%2Fsvg%3E');
            width: 100px;
            height: 100px;
            background-size: 100% 100%;
            background-repeat: no-repeat;
        }
        
        .three {
            background: url('data:image/svg+xml,%3Csvg%20version%3D%221.1%22%0A%09%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20xmlns%3Axlink%3D%22http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink%22%20xmlns%3Aa%3D%22http%3A%2F%2Fns.adobe.com%2FAdobeSVGViewerExtensions%2F3.0%2F%22%0A%09%20x%3D%220px%22%20y%3D%220px%22%20width%3D%2281px%22%20height%3D%2280.5px%22%20viewBox%3D%220%200%2081%2080.5%22%20style%3D%22overflow%3Ascroll%3Benable-background%3Anew%200%200%2081%2080.5%3B%22%0A%09%20xml%3Aspace%3D%22preserve%22%3E%0A%3Cstyle%20type%3D%22text%2Fcss%22%3E%0A%09.st0%7Bfill%3A%23383838%3B%7D%0A%3C%2Fstyle%3E%0A%3Cdefs%3E%0A%3C%2Fdefs%3E%0A%3Cpath%20class%3D%22st0%22%20d%3D%22M30.3%2C68.2c1.2%2C0.2%2C2.3%2C0.9%2C3.8%2C1.2c1.6%2C0.3%2C2.7%2C0.6%2C4%2C0.4l4.9%2C9.6c0.6%2C0.9%2C1.4%2C1.1%2C2.3%2C0.9l15.3-4.9%0A%09c0.5-0.3%2C1-1%2C0.9-2.3l-1.8-10.6c2-1.6%2C3.6-3.7%2C5.3-5.8l10.5%2C0.6c1.1%2C0.6%2C2.1-0.4%2C2.3-1.1L81%2C40.7c0.2-0.8-0.4-2.1-1.1-2.3l-10.2-3.8%0A%09c-0.3-2.5-1.4-4.8-2.5-7.5l5.9-8.5c0.6-1.1%2C0.4-1.9-0.2-2.9l-12-10.7c-0.3-0.5-1.6-0.3-2.5%2C0.3l-8%2C6.9c-1.2-0.2-2.3-0.9-3.8-1.2%0A%09c-1.6-0.3-2.7-0.6-4-0.4L37.7%2C1c-0.6-0.9-1.4-1.1-2.3-0.9L20.1%2C5c-0.5%2C0.3-1%2C1-0.9%2C2.3l1.8%2C10.6c-2%2C1.6-3.6%2C3.7-5.3%2C5.8L5.3%2C23%0A%09c-0.8-0.2-1.7%2C0.4-2%2C1.6L0%2C40.2c-0.2%2C0.8%2C0.4%2C2.1%2C1.1%2C2.3l9.8%2C3.7c0.7%2C2.6%2C1.4%2C5.2%2C2.5%2C7.5l-6%2C8.9c-0.6%2C0.7-0.4%2C2%2C0.3%2C2.5l12%2C10.7%0A%09c0.7%2C0.5%2C1.9%2C0.8%2C2.4%2C0.1L30.3%2C68.2z%20M26.7%2C37.3c1.6-7.4%2C9.1-12.3%2C16.5-10.8S55.6%2C35.7%2C54%2C43.1c-1.6%2C7.4-9.1%2C12.3-16.5%2C10.7%0A%09C30.1%2C52.3%2C25.1%2C44.7%2C26.7%2C37.3L26.7%2C37.3z%22%2F%3E%0A%3C%2Fsvg%3E');
            width: 80px;
            height: 80px;
            background-size: 100% 100%;
            background-repeat: no-repeat;
            margin-top: -50px;
            margin-left: -10px;
        }

        @keyframes spin-one {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }

            100% {
                -webkit-transform: rotate(-359deg);
                transform: rotate(-359deg);
            }
        }

        .spin-one {
            -webkit-animation: spin-one 1.5s infinite linear;
            animation: spin-one 1.5s infinite linear;
        }

        @keyframes spin-two {
            0% {
                -webkit-transform: rotate(0deg);
                transform: rotate(0deg);
            }

            100% {
                -webkit-transform: rotate(-359deg);
                transform: rotate(359deg);
            }
        }

        .spin-two {
            -webkit-animation: spin-two 2s infinite linear;
            animation: spin-two 2s infinite linear;
        }

        .day,
        .hour,
        .minute,
        .second {
            font-size: 18px;
            background: #333;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin: 5px;
            flex: 1;
            text-align: center;
        }

        .day {
            background-color: #1abc9c;
        }

        .hour {
            background-color: #3498db;
        }

        .minute {
            background-color: #f1c40f;
        }

        .second {
            background-color: #e74c3c;
        }

        .countdown {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 24px;
            }

            p {
                font-size: 14px;
            }

            .one,
            .two,
            .three {
                width: 40px;
                height: 40px;
            }

            .day,
            .hour,
            .minute,
            .second {
                font-size: 14px;
                padding: 8px;
                margin: 2px;
            }
        }
    </style>

</head>

<body>
    <!-- partial:index.partial.html -->
    <link href="https://fonts.googleapis.com/css?family=Noto+Sans:400,700" rel="stylesheet">

    <div class="container">
        <div class="box">
            <div class="animation">
                <div class="one spin-one"></div>
                <div class="two spin-two"></div>
                <div class="three spin-one"></div>
            </div>
            <h1>{Lang::T('Site is temporarily unavailable.')}</h1>
            <p>{Lang::T('Scheduled maintenance is currently in progress. Please check back soon.')}</p>
            <p>{Lang::T('We apologize for any inconvenience.')} <br> &mdash; {Lang::T('The ')} {$companyName} {Lang::T('
                Team.')}</p>
            <br>
            {if $date} <div style="display: flex; flex-direction: row; justify-content: space-between;">
                <p class="day"></p>
                <p class="hour"></p>
                <p class="minute"></p>
                <p class="second"></p>
            </div>
            {/if}
        </div>
    </div>
    {if $date}
    <script>
        const countDown = () => {
            const countDay = new Date('{$date}');
            const now = new Date();
            const counter = countDay - now;
            const second = 1000;
            const minute = second * 60;
            const hour = minute * 60;
            const day = hour * 24;
            const textDay = Math.floor(counter / day);
            const textHour = Math.floor((counter % day) / hour);
            const textMinute = Math.floor((counter % hour) / minute);
            const textSecond = Math.floor((counter % minute) / second)
            document.querySelector(".day").innerText = textDay + ' Days';
            document.querySelector(".hour").innerText = textHour + ' Hours';
            document.querySelector(".minute").innerText = textMinute + ' Minutes';
            document.querySelector(".second").innerText = textSecond + ' Seconds';
        }
        setInterval(countDown, 1000);
    </script>
    {/if}
</body>

</html>