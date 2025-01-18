<?php
// Setting headers for JSON response
header('Content-Type: application/json');

// Require dependencies
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file is uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error.']);
        exit;
    }

    // Validate file type
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/bmp', 'image/tiff'];
    if (!in_array($_FILES['file']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDFs and images are allowed.']);
        exit;
    }

    // Set up the upload directory
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        exit;
    }

    // Rename file with a timestamp to avoid collisions
    $timestamp = time();
    $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $newFileName = $timestamp . '.' . $extension;
    $filePath = $uploadDir . $newFileName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save the uploaded file.']);
        exit;
    }

    // Validate length parameter
    $numSentences = isset($_POST['length']) ? (int) $_POST['length'] : null;
    if (empty($numSentences) || $numSentences <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing length parameter.']);
        exit;
    }

    try {
        $text = '';

        // Process file based on its type
        if ($extension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'bmp', 'tiff'])) {
            $text = (new TesseractOCR($filePath))->lang('eng')->run();
        } else {
            echo json_encode(['success' => false, 'message' => 'Unsupported file type.']);
            exit;
        }

        $summaryRequest = [
            'lang' => 'en',
            'text' => $text . " Make summary in $numSentences sentences."
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://article-extractor-and-summarizer.p.rapidapi.com/summarize-text",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($summaryRequest),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "x-rapidapi-host: article-extractor-and-summarizer.p.rapidapi.com",
                "x-rapidapi-key: 4c7c2786bamsh23c8a010d686a85p19c2dfjsncc248c301769"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo json_encode([
                'success' => false,
                'message' => 'API error: ' . $err,
                'fileName' => $newFileName
            ]);
            exit;
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['summary'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to summarize text. Check API response.',
                'fileName' => $newFileName,
                'apiResponse' => $responseData
            ]);
            exit;
        }

        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded and processed successfully.',
            'fileName' => $newFileName,
            'summary' => $responseData['summary'],
            'extractedText' => $text
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error processing the file: ' . $e->getMessage(),
        ]);
    }

    exit;
}

// Invalid request method response
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);

?>
