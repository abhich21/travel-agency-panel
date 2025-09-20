<?php
session_start();
require_once '../config/config.php';

// Redirect to login if not logged in as a promoter
if (!isset($_SESSION['promoterLoggedIn']) || !$_SESSION['promoterLoggedIn'] === TRUE) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>QR Code Scanner</title>
    <script src="https://unpkg.com/html5-qrcode@2.0.9/dist/html5-qrcode.min.js"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .scanner-container {
            max-width: 500px;
            margin: 2rem auto;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow: hidden;
            background-color: #ffffff;
            padding: 2rem;
        }
        #reader {
            width: 100%;
        }
        .text-center {
            text-align: center;
        }
        .jumbotron {
            background-color: #e9ecef;
            border-radius: .5rem;
            padding: 2rem;
        }
        .btn-check-in {
            background-color: #28a745;
            color: white;
        }
        .btn-check-out {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0 py-4">
        <div class="container">
            <h2 class="mb-4 text-center text-secondary">Scan User QR Code</h2>
            <div class="scanner-container">
                <div id="reader"></div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function showSweetAlert(icon, title, message) {
                return Swal.fire({
                    icon: icon,
                    title: title,
                    text: message,
                    timer: 3000,
                    showConfirmButton: false
                });
            }

            const qrCodeReader = new Html5QrcodeScanner("reader", {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                supportedScanFormats: [Html5QrcodeSupportedFormats.QR_CODE]
            }, false);

            function startScanner() {
                qrCodeReader.render(onScanSuccess, onScanError);
            }

            function onScanSuccess(decodedText, decodedResult) {
                console.log(`Code matched = ${decodedText}`, decodedResult);
                
                qrCodeReader.clear().then(() => {
                    // const qrDataLines = decodedText.split('\n');
                    let uniqueId = decodedText;

                    // for (const line of qrDataLines) {
                    //     if (line.includes("Email:") || line.includes("Mobile:")) {
                    //         uniqueId = line.split(":").slice(1).join(":").trim();
                    //         break;
                    //     }
                    // }

                    if (uniqueId) {
                        console.log(`Extracted Unique ID being sent: ${uniqueId}`);
                        
                        $.ajax({
                            url: 'api/toggle_arrival.php',
                            type: 'POST',
                            data: { unique_id: uniqueId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    showSweetAlert('success', 'Success!', response.message)
                                        .then(() => startScanner());
                                } else {
                                    showSweetAlert('error', 'Error!', response.message)
                                        .then(() => startScanner());
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR.responseText);
                                showSweetAlert('error', 'Error!', 'An error occurred. Please check the console for details.')
                                    .then(() => startScanner());
                            }
                        });
                    } else {
                        showSweetAlert('error', 'Error!', 'Invalid QR code data.')
                            .then(() => startScanner());
                    }
                }).catch(err => {
                    console.error("Failed to stop scanner:", err);
                    showSweetAlert('error', 'Scanner Error!', 'An unexpected error occurred. Please refresh the page.');
                });
            }

            function onScanError(errorMessage) {
                if (errorMessage.includes("QR code parse error")) {
                    return;
                }

                console.error("Scanner error:", errorMessage);

                if (errorMessage.includes("NotAllowedError") || errorMessage.includes("NotFoundError")) {
                    qrCodeReader.stop().then(() => {
                        if (errorMessage.includes("NotAllowedError")) {
                            showSweetAlert('error', 'Permission Denied!', 'Please allow camera access in your browser settings.');
                        } else {
                            showSweetAlert('error', 'No Camera Found!', 'Please ensure a camera is connected and try again.');
                        }
                    }).catch(err => {
                        console.error("Failed to stop scanner:", err);
                    });
                }
            }
            
            startScanner();
        });
    </script>
</body>
</html>
