<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>File to Summary</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Besley:ital,wght@0,400..900;1,400..900&family=Raleway:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        
        <style>
            body {
                font-family: "Besley", serif;
            }
            .fw-800 { font-weight: 800; }
            .fw-600 { font-weight: 600; }
            .drop-zone {
                height: 300px;
                padding: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                font-weight: 500;
                font-size: 15px;
                cursor: pointer;
                color: #787878;
                border: 4px dashed #198754;
                border-radius: 20px;
            }
            .drop-zone--over { border-style: solid; }
            .drop-zone__input { display: none; }
            .drop-zone__thumb {
                width: 100%;
                height: 100%;
                border-radius: 10px;
                overflow: hidden;
                background-color: #cccccc;
                background-size: cover;
                position: relative;
            }
            .drop-zone__thumb::after {
                content: attr(data-label);
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 5px 0;
                color: #ffffff;
                background: rgba(0, 0, 0, 0.75);
                font-size: 14px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <form class="row g-3 py-3 needs-validation text-center mx-auto" style="max-width: 600px;" novalidate>
                <div class="col-12">
                    <h1 class="h2 fw-800 text-success mb-0">UnthinkableAI</h1>
                    <h3 class="h5 fw-600 mb-1">Summarize any file with AI</h3>
                    <small class="text-secondary">JPG • JPEG • PNG • BMP • TIFF • PDF</small>
                </div>
                <div class="col-12">
                    <div class="drop-zone">
                        <span class="drop-zone__prompt">Drop file here or click to upload</span>
                        <input type="file" name="myFile" class="drop-zone__input" id="formFile" required>
                    </div>
                </div>
                <div class="col-12">
                    <label for="formLength" class="form-label text-secondary fw-600">Summary Length</label> &nbsp;
                    <input type="radio" class="btn-check" name="formLength" id="btnradio1" value="3" autocomplete="off" checked>
                    <label class="btn btn-sm btn-outline-success rounded-pill" for="btnradio1">Short</label>

                    <input type="radio" class="btn-check" name="formLength" id="btnradio2" value="7" autocomplete="off">
                    <label class="btn btn-sm btn-outline-success rounded-pill" for="btnradio2">Medium</label>

                    <input type="radio" class="btn-check" name="formLength" id="btnradio3" value="11" autocomplete="off">
                    <label class="btn btn-sm btn-outline-success rounded-pill" for="btnradio3">Long</label>
                </div>
                <div class="col-12" id="btnDiv">
                    <button class="btn btn-success w-100 fw-600" type="submit" id="getSummary">Generate Summary</button>
                </div>
                <div class="col-12 text-center">
                    <div class="alert alert-success" role="alert" id="getSum">
                        Your summary will appear here.
                    </div>
                </div>
            </form>
        </div>

        <script>
            const toggleSpinner = (show) => {
                const element = document.getElementById("btnDiv");
                element.innerHTML = show 
                    ? '<div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>'
                    : '<button class="btn btn-success w-100 fw-600" type="submit" id="getSummary">Generate Summary</button>';
                
                if (!show) {
                    document.querySelector("#getSummary").addEventListener('click', handleSummaryClick);
                }
            };

            const handleSummaryClick = (e) => {
                e.preventDefault();
                toggleSpinner(true);

                const formFile = document.querySelector('#formFile');
                const formLength = document.querySelector('input[name="formLength"]:checked');
                const allowedTypes = ['image/jpeg', 'image/png', 'image/bmp', 'application/pdf'];

                if (!formFile.files[0] || !allowedTypes.includes(formFile.files[0].type)) {
                    alert("Please upload a valid file (JPG, PNG, BMP, or PDF).");
                    toggleSpinner(false);
                    return;
                }

                const formData = new FormData();
                formData.append('file', formFile.files[0]);
                formData.append('length', formLength.value);

                fetch('upload.php', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('getSum').innerHTML = data.summary || "Summary generated successfully!";
                        } else {
                            alert(data.message || "An error occurred!");
                        }
                    })
                    .catch(err => {
                        console.error('Upload error:', err);
                        alert("Failed to upload the file.");
                    })
                    .finally(() => toggleSpinner(false));
            };

            document.querySelector('#getSummary').addEventListener('click', handleSummaryClick);

            document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
                const dropZoneElement = inputElement.closest(".drop-zone");

                dropZoneElement.addEventListener("click", (e) => {
                    inputElement.click();
                });

                inputElement.addEventListener("change", (e) => {
                    if (inputElement.files.length) {
                        updateThumbnail(dropZoneElement, inputElement.files[0]);
                    }
                });

                dropZoneElement.addEventListener("dragover", (e) => {
                    e.preventDefault();
                    dropZoneElement.classList.add("drop-zone--over");
                });

                ["dragleave", "dragend"].forEach((type) => {
                    dropZoneElement.addEventListener(type, (e) => {
                        dropZoneElement.classList.remove("drop-zone--over");
                    });
                });

                dropZoneElement.addEventListener("drop", (e) => {
                    e.preventDefault();
                    if (e.dataTransfer.files.length) {
                        inputElement.files = e.dataTransfer.files;
                        updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
                    }
                    dropZoneElement.classList.remove("drop-zone--over");
                });
            });

            function updateThumbnail(dropZoneElement, file) {
                let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

                if (dropZoneElement.querySelector(".drop-zone__prompt")) {
                    dropZoneElement.querySelector(".drop-zone__prompt").remove();
                }

                if (!thumbnailElement) {
                    thumbnailElement = document.createElement("div");
                    thumbnailElement.classList.add("drop-zone__thumb");
                    dropZoneElement.appendChild(thumbnailElement);
                }

                thumbnailElement.dataset.label = file.name;

                if (file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = () => {
                        thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
                    };
                } else {
                    thumbnailElement.style.backgroundImage = null;
                }
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    </body>
</html>
