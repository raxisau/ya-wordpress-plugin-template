<?php

namespace Jackbooted\Admin;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
class ImagePositionLocator extends WebPage {

    protected function zoom() {
        $siteUrl = Cfg::siteUrl();

        $html = '';
        $html .= JS::library( JS::JQUERY );

        // Get the current Pin
        $url = Request::get( 'url' );

        $jQuery = <<<JS
    var currentXPos = 0;
    var currentYPos = 0;
    var IE = document.all?true:false
    if (!IE) document.captureEvents(Event.MOUSEMOVE);
    document.onmousemove = getMouseXY;
    function getMouseXY(e) {
        if (IE) { // grab the x-y pos.s if browser is IE
            currentXPos = event.clientX + document.body.scrollLeft;
            currentYPos = event.clientY + document.body.scrollTop;
        } else {  // grab the x-y pos.s if browser is NS
            currentXPos = e.pageX;
            currentYPos = e.pageY;
        }
        if (currentXPos < 0) currentXPos = 0;
        if (currentYPos < 0) currentYPos = 0;
        return true;
    }
    function movePinToCursor () {
       var offs = $('#baseImage').offset();
       $('#PinTop').attr ( 'value', '' + parseInt ( currentYPos - offs.top ) );
       $('#PinLeft').attr ( 'value', '' + parseInt ( currentXPos - offs.left ) );
    }
JS;

        $html .= JS::javaScript( $jQuery );

        $html .= Tag::img( $siteUrl . $url, [ 'title' => 'Click on this image to move the Pin',
                    'id' => 'baseImage',
                    'onClick' => 'movePinToCursor();',
                    'name' => 'voodoo_image' ] );
        $html .= '<br>X' . Tag::text( 'PinLeft', '', [ 'size' => 4, 'id' => 'PinLeft' ] );
        $html .= '<br>Y' . Tag::text( 'PinTop', '', [ 'size' => 4, 'id' => 'PinTop' ] );

        return $html;
    }

    /**
     * Searches all the files in the passed directory and scans them for classes
     * @param string $classesDir
     */
    private function findImages( $searchDir ) {
        $items = [];
        $handle = opendir( $searchDir );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.' ) === 0 )
                continue;
            if ( strpos( $file, '_private' ) === 0 )
                continue;
            if ( strpos( $file, 'thumbs' ) === 0 )
                continue;

            $fullPathName = $searchDir . '/' . $file;
            if ( is_dir( $fullPathName ) )
                $items = array_merge( $items, $this->findImages( $fullPathName ) );
            else if ( preg_match( '/^.*\.(jpg|jpeg|png|gif)$/i', $file ) )
                $items[] = $fullPathName;
        }
        closedir( $handle );
        return $items;
    }

    public function index() {

        $sitePath = Cfg::get( 'site_path' );
        $sitePathLen = strlen( $sitePath );
        $resp = Response::factory()->action( __CLASS__ . '->zoom()' );

        $html = Tag::ul();
        foreach ( $this->findImages( $sitePath ) as $item ) {
            $relItemName = substr( $item, $sitePathLen );
            $html .= Tag::li() .
                    Tag::hRef( '?' . $resp->set( 'url', $relItemName )->toUrl(), $relItemName ) .
                    Tag::_li();
        }
        $html .= Tag::_ul();

        return $html;
    }

}
