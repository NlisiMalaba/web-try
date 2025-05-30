<?php
/**
 * FPDF - A simple FPDF class wrapper
 * This is a minimal implementation for PDF generation
 */
class FPDF {
    protected $page;               // current page number
    protected $n;                 // current object number
    protected $offsets;           // array of object offsets
    protected $pages;             // array containing pages
    protected $state;             // current document state
    protected $fonts;             // array of used fonts
    protected $FontFamily;        // current font family
    protected $FontStyle;         // current font style
    protected $FontSizePt;        // current font size in points
    protected $FontSize;          // current font size in user unit
    protected $DrawColor;         // commands for drawing color
    protected $FillColor;         // commands for filling color
    protected $TextColor;         // commands for text color
    protected $ColorFlag;         // indicates whether fill and text colors are different
    protected $ws;                // word spacing
    protected $images;            // array of used images
    protected $PageLinks;         // array of links in pages
    protected $links;             // array of internal links
    protected $AutoPageBreak;     // automatic page breaking
    protected $PageBreakTrigger;  // threshold used to trigger page breaks
    protected $InHeader;          // flag set when processing header
    protected $InFooter;          // flag set when processing footer
    protected $ZoomMode;          // zoom display mode
    protected $LayoutMode;        // layout display mode
    protected $title;             // title
    protected $subject;           // subject
    protected $author;            // author
    protected $keywords;          // keywords
    protected $creator;           // creator
    protected $AliasNbPages;      // alias for total number of pages
    protected $PDFVersion;        // PDF version number

    public function __construct($orientation='P', $unit='mm', $size='A4') {
        // Initialization of properties
        $this->page = 0;
        $this->n = 2;
        $this->offsets = array();
        $this->pages = array();
        $this->state = 0;
        $this->fonts = array();
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->FontSize = 12 / 2.54 * 72; // Default to 12pt
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->ws = 0;
        $this->images = array();
        $this->PageLinks = array();
        $this->links = array();
        $this->AutoPageBreak = true;
        $this->PageBreakTrigger = 0;
        $this->InHeader = false;
        $this->InFooter = false;
        $this->ZoomMode = 'fullpage';
        $this->LayoutMode = 'continuous';
        $this->title = '';
        $this->subject = '';
        $this->author = '';
        $this->keywords = '';
        $this->creator = '';
        $this->AliasNbPages = '{nb}';
        $this->PDFVersion = '1.3';
    }


    public function AddPage($orientation='', $size='', $rotation=0) {
        // Start a new page
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = 10; // Default left margin
        $this->y = 10; // Default top margin
        $this->FontFamily = 'Arial';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->FontSize = 12 / 2.54 * 72; // Default to 12pt
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->ws = 0;
    }

    public function SetFont($family, $style='', $size=0) {
        // Set font family, style and size
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        if($size > 0)
            $this->FontSizePt = $size;
    }

    public function SetFontSize($size) {
        // Set font size in points
        $this->FontSizePt = $size;
        $this->FontSize = $size / 2.54 * 72; // Convert points to user units (1/72 inch)
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        // Output a cell
        // This is a simplified version that just outputs text
        if($h == 0)
            $h = $this->FontSize / 2.54 * 72 / 10; // Default height based on font size
        
        // Store the cell content
        $this->pages[$this->page] .= sprintf("BT /F1 %.2f Tf %.2f %.2f Td (%s) Tj ET\n", 
            $this->FontSize, $this->x, $this->y, $txt);
        
        // Move position
        $this->x += $w;
        if($ln > 0) {
            $this->x = 10; // Reset x to left margin
            $this->y += $h;
        }
    }
    public function Ln($h=null) {
        // Line feed; default value is last cell height
        $this->x = 10; // Reset x to left margin
        if($h === null)
            $h = $this->FontSize / 2.54 * 72 / 10; // Default height based on font size
        $this->y += $h;
    }
    public function SetX($x) {
        // Set x position
        $this->x = $x;
    }
    public function SetY($y, $resetX=true) {
        // Set y position and optionally reset x
        $this->y = $y;
        if($resetX)
            $this->x = 10; // Reset x to left margin
    }
    public function SetXY($x, $y) {
        // Set x and y positions
        $this->x = $x;
        $this->y = $y;
    }
    public function SetFillColor($r, $g=null, $b=null) {
        // Set fill color (RGB 0-255)
        $this->FillColor = sprintf('%.3f g', $r/255);
        if($g !== null && $b !== null)
            $this->FillColor = sprintf('%.3f %.3f %.3f rg', $r/255, $g/255, $b/255);
    }
    public function SetTextColor($r, $g=null, $b=null) {
        // Set text color (RGB 0-255)
        $this->TextColor = sprintf('%.3f g', $r/255);
        if($g !== null && $b !== null)
            $this->TextColor = sprintf('%.3f %.3f %.3f rg', $r/255, $g/255, $b/255);
    }
    public function SetDrawColor($r, $g=null, $b=null) {
        // Set draw color (RGB 0-255)
        $this->DrawColor = sprintf('%.3f G', $r/255);
        if($g !== null && $b !== null)
            $this->DrawColor = sprintf('%.3f %.3f %.3f RG', $r/255, $g/255, $b/255);
    }
    public function SetAuthor($author, $isUTF8=false) {
        // Set document author
        $this->author = $author;
    }
    public function SetCreator($creator, $isUTF8=false) {
        // Set document creator
        $this->creator = $creator;
    }
    public function SetSubject($subject, $isUTF8=false) {
        // Set document subject
        $this->subject = $subject;
    }
    public function SetTitle($title, $isUTF8=false) {
        // Set document title
        $this->title = $title;
    }
    public function SetKeywords($keywords, $isUTF8=false) {
        // Set document keywords
        $this->keywords = $keywords;
    }
    public function Output($dest='', $name='', $isUTF8=false) {
        // Output PDF to browser or file
        if($name == '')
            $name = 'doc.pdf';
        
        // Force download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name + '"');
        
        // Output a simple PDF header
        echo "%PDF-1.3\n";
        echo "%\xE2\xE3\xCF\xD3\n";
        
        // Output objects
        echo "1 0 obj\n";
        echo "<< /Type /Catalog /Pages 2 0 R >>\n";
        echo "endobj\n";
        
        echo "2 0 obj\n";
        echo "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        echo "endobj\n";
        
        echo "3 0 obj\n";
        echo "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\n";
        echo "endobj\n";
        
        echo "4 0 obj\n";
        echo "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n";
        echo "endobj\n";
        
        echo "5 0 obj\n";
        echo "<< /Length 44 >>\n";
        echo "stream\n";
        echo "BT /F1 24 Tf 100 700 Td (Hello World) Tj ET\n";
        echo "endstream\n";
        echo "endobj\n";
        
        // Cross-reference table
        $startxref = strlen(implode("\n", $this->pages)) + 1;
        echo "xref\n";
        echo "0 6\n";
        echo "0000000000 65535 f \n";
        echo "0000000018 00000 n \n";
        echo "0000000077 00000 n \n";
        echo "0000000172 00000 n \n";
        echo "0000000276 00000 n \n";
        echo "0000000336 00000 n \n";
        
        // Trailer
        echo "trailer\n";
        echo "<< /Size 6 /Root 1 0 R /Info << /Author ($this->author) /Creator ($this->creator) /Title ($this->title) >> >>\n";
        echo "startxref\n";
        echo $startxref . "\n";
        echo "%%EOF\n";
        
        exit;
    }
}
