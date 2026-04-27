<!DOCTYPE html>
<html>

<head>
    <title>Medical Report Upload Test</title>
</head>

<body>

    <h2>Test Medical Report Upload</h2>

    <form id="uploadForm" enctype="multipart/form-data">

        <!-- If your API uses auth middleware, paste a valid token -->
        <input type="hidden" name="Authorization" value="Bearer YOUR_TOKEN_HERE">

        <label>Name:</label>
        <input type="text" name="name" required><br><br>

        <label>Type:</label>
        <input type="text" name="type" value="blood_test" required><br><br>

        <label>Report Date:</label>
        <input type="date" name="report_date" required><br><br>

        <label>Is Public:</label>
        <input type="number" name="is_public" value="0"><br><br>

        <label>Select File:</label>
        <input type="file" name="file" required><br><br>

        <button type="submit">Upload</button>
    </form>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const response = await fetch(
                "https://stagetelehealth.cmcludhiana.in/api/v2/patient/1b646343-cb3d-4596-9009-75f6041be66f/medical-reports", {
                    method: "POST",
                    headers: {
                        "Authorization": "Bearer 44|B8ctIlIg1RcLRs7rgAn9Jm83CzvVUZ0iHGG5zJGz074c536c",
                        "Accept": "application/json"
                    },
                    body: formData
                }
            );

            // Read text first (because 500 errors may not return JSON)
            const responseText = await response.text();
            console.log("Raw server response:", responseText);

            // Now check HTTP status
            if (!response.ok) {
                console.error("Server returned error:", response.status, responseText);
                alert("Upload failed: " + response.status);
                return;
            }

            // If success, parse JSON
            const data = JSON.parse(responseText);
            console.log("Upload success:", data);
            alert("Uploaded successfully!");

        });
    </script>
</body>

</html>
