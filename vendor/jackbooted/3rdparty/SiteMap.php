<?php

class SiteMap {

    private $pages = [];
    private $file;

    public function __construct( $file = null ) {
        $this->file = $file;
    }

    public function create() {
        $str = $this->xmlHeader();
        $str .= $this->getPages();
        $str .= $this->xmlFooter();
        $this->write2file( $this->file, $str );
    }

    private function xmlHeader() {
        $str = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
        xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
XML;
        return $str;
    }

    private function xmlFooter() {
        $str = '
        </urlset>
        ';
        return $str;
    }

    private function getPages() {
        for ( $i = 0; $i < count( $this->pages['url'] ); $i++ ) {
            $str .= '
            <url>
                <loc>' . $this->pages['url'][$i] . '</loc>
                <lastmod>' . date( 'Y-m-d' ) . 'T' . date( 'H:i:s' ) . '+00:00</lastmod>
                <changefreq>' . $this->pages['frecvent'][$i] . '</changefreq>
                <priority>' . $this->pages['priority'][$i] . '</priority>
            </url>
            ';
        }
        return $str;
    }

    public function addPage( $url, $frecvent = 'daily', $priority = 1.0 ) {
        $this->pages['url'][] = $url;
        $this->pages['frecvent'][] = $frecvent;
        $this->pages['priority'][] = $priority;
    }

    public function write2file( $fname, $string ) {
        if ( $fname == null ) {
            echo $string;
        }
        else {
            unlink( $fname );
            file_put_contents( $fname, $string );
        }
    }

}
