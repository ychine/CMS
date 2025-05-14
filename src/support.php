<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support | CourseDock</title>
    <link href="../img/cdicon.svg" rel="icon">
    <link href="styles.css" rel="stylesheet">
    <link href="tailwind/output.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        .animated-bg {
            background: radial-gradient(circle at 50% 100%, #304589, #101836, #040a1d);
            background-size: 100% 100%;
            background-position: center bottom;
            animation: pulse 18s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        @keyframes pulse {
            0% {
                background-size: 100% 100%;
                background-position: center bottom;
            }
            25% {
                background-size: 125% 125%;
                background-position: center bottom;
            }
            50% {
                background-size: 150% 150%;
                background-position: center bottom;
            }
            75% {
                background-size: 125% 125%;
                background-position: center bottom;
            }
            100% {
                background-size: 100% 100%;
                background-position: center bottom;
            }
        }

        @keyframes popup {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .content-box {
            box-shadow: inset 0 0 20px 2px #304374;
            padding: 2rem;
            border-radius: 10px;
            width: 100%;
            border: 0.5px solid #3d74ff;
            animation: popup 2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .back-link {
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        .title-underline {
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }

        .title-underline::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, #3d74ff, transparent);
            transform: scaleX(0);
            transform-origin: left;
            animation: underlineAnim 2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes underlineAnim {
            0% {
                transform: scaleX(0);
                opacity: 0;
            }
            100% {
                transform: scaleX(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="animated-bg">

    <div class="header -mt-5 flex-col flex items-center justify-center">
        <img src="../img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>

    <div class="flex justify-center min-h-screen mt-20">
        <div class="w-full max-w-[700px] flex flex-col items-center px-6 relative z-10">
            <div class="relative text-center mb-5 font-onest">
                <div class="fade-in text-3xl mb-5 font-semibold text-[#E3E3E3] title-underline">Contact our Support Team</div>
            </div>
            
            <div class="content-box fade-in">
                <div class="text-[#E3E3E3] text-lg font-onest font-light leading-relaxed space-y-4">
                
                    <p class="mb-6">
                    For any inquiries or support, please submit a ticket using the form below:
                    <hr class="border-none border-t-0.2 border-gray-500">

                    </p>
                    Email
                    <div class="tf">
                        <input type="text" class="tf ">
                    </div>
                    Subject
                    <div class="tf">
                        <input type="text" class="tf ">
                    </div>
                    Message
                    <div class="tf">
                        <input type="text" class="tf ">
                    </div>
                    
                </div>
            </div>

            <div onclick="history.back()" class="text-center text-[#E3E3E3] mt-7 text-sm font-overpass back-link">Go Back</div>
        </div>
    </div>
</body>

</html>