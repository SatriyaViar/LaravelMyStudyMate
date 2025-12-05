<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;

class FileExtractorService
{
    /**
     * Extract text from uploaded file (PDF or DOCX)
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public function extractText($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            switch ($extension) {
                case 'pdf':
                    return $this->extractFromPdf($file);
                case 'docx':
                case 'doc':
                    return $this->extractFromDocx($file);
                case 'txt':
                    return file_get_contents($file->getRealPath());
                default:
                    throw new \Exception("Unsupported file type: {$extension}");
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract text: " . $e->getMessage());
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractFromPdf($file)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getRealPath());
        $text = $pdf->getText();

        // Clean up extracted text
        $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces
        $text = trim($text);

        if (empty($text)) {
            throw new \Exception("No text found in PDF file");
        }

        return $text;
    }

    /**
     * Extract text from DOCX file
     */
    private function extractFromDocx($file)
    {
        // Use simple ZIP-based extraction (most reliable)
        return $this->extractDocxSimple($file);
    }

    /**
     * Simple DOCX text extraction (fallback method)
     */
    private function extractDocxSimple($file)
    {
        $text = '';
        
        try {
            // Read DOCX as ZIP archive
            $zip = new \ZipArchive();
            
            if ($zip->open($file->getRealPath()) === true) {
                // Get document.xml which contains the text
                $xml = $zip->getFromName('word/document.xml');
                
                if ($xml !== false) {
                    // Parse XML and extract text
                    $dom = new \DOMDocument();
                    $dom->loadXML($xml);
                    
                    // Get all text nodes
                    $texts = $dom->getElementsByTagName('t');
                    
                    foreach ($texts as $textNode) {
                        $text .= $textNode->nodeValue . ' ';
                    }
                }
                
                $zip->close();
            }
            
            // Clean up
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (empty($text)) {
                throw new \Exception("No text found in DOCX file");
            }
            
            return $text;
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract DOCX: " . $e->getMessage());
        }
    }

    /**
     * Validate file type
     */
    public function isValidFileType($file)
    {
        $allowedExtensions = ['pdf', 'docx', 'doc', 'txt'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        return in_array($extension, $allowedExtensions);
    }

    /**
     * Validate file size (max 10MB)
     */
    public function isValidFileSize($file, $maxSizeMB = 10)
    {
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;
        return $file->getSize() <= $maxSizeBytes;
    }
}
