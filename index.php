<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle file upload
    if (isset($_FILES["audio"]) && $_FILES["audio"]["error"] == UPLOAD_ERR_OK) {
        // Save uploaded file to temporary directory
        $tmp_name = $_FILES["audio"]["tmp_name"];
        $name = basename($_FILES["audio"]["name"]);
        $name = preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
        $name_without_ext = pathinfo($name, PATHINFO_FILENAME); // Extract file name without extension
        $upload_dir = "/var/www/html/whisper/uploads/";
        $audio_path = $upload_dir . $name;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        move_uploaded_file($tmp_name, $audio_path);

        // Use Whisper to generate subtitles
        $whisper_path = "/var/www/.local/bin/whisper";
        $subtitles_path = $upload_dir . "subtitles.txt";
        exec("$whisper_path $audio_path --model tiny.en > $subtitles_path");

        // Offer subtitle file as download
        if (file_exists($subtitles_path)) {

            $zipname = $name_without_ext . '.zip';
            $zip = new ZipArchive;
            $zip->open($zipname, ZipArchive::CREATE);
            $zip->addFile("$name_without_ext.txt");
            $zip->addFile("$name_without_ext.srt");
            $zip->close();

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $zipname . '"');
            header('Content-Length: ' . filesize($zipname));
            readfile($zipname);

            // Delete uploaded file and subtitle file
            unlink($audio_path);
            unlink($subtitles_path);
            unlink("./$name_without_ext.srt");
            unlink("./$name_without_ext.json");
            unlink("./$name_without_ext.vtt");
            unlink("./$name_without_ext.tsv");
            unlink($zipname);
            unlink("./$name_without_ext.txt");

            exit();
        } else {
            echo "Error generating subtitles";
        }
    } else {
        echo "Error uploading file";
    }

}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Whisper Subtitle Generator</title>
</head>

<body>
    <h1>Whisper Subtitle Generator</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="audio">
        <input type="submit" value="Generate Subtitles">
    </form>
</body>

</html>
