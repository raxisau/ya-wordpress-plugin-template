<?php

namespace Jackbooted\Util;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;

/*
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 */

/**
 */
class MenuUtils extends WebPage {

    const ACTIVE_MENU = 'ACTIVE_MENU';

    public static function display( $menuClasses = null ) {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $id = 'MenuUtils_display' . Invocation::next();
        $jsLibraries = JS::libraryWithDependancies( JS::JQUERY_UI );
        $activeMenu = Request::get( self::ACTIVE_MENU, 0 );
        $js = <<<JS
            $().ready ( function () {
                $( '#$id' ).show()
                           .accordion({
                    collapsible: true,
                    active: $activeMenu
                });
            });
JS;

        $html = '';
        $html .= Tag::div( [ 'id' => $id, 'style' => 'font-size: 0.8em; width:250px; text-align:left; display:none;' ] );
        foreach ( self::getMenuItems( $menuClasses ) as $header => $menuList ) {
            $html .= Tag::hTag( 'h3' ) . Tag::hRef( '#', $header ) . Tag::_hTag( 'h3' ) .
                     Tag::div() .
                       Tag::ul();
            foreach ( $menuList as $row ) {
                if ( $row['name'] == '--' ) {
                    $html .= Tag::_ul() .
                             Tag::hTag( 'hr' ) .
                             Tag::ul();
                }
                else {
                    $html .= Tag::li();
                    if ( isset( $row['slug'] ) ) {
                        $html .= Tag::hRef( Cfg::siteUrl() . '/menu.php?S=' . $row['slug'], $row['name'], $row['attribs'] );
                    }
                    else {
                        $html .= Tag::hRef( $row['url'], $row['name'], $row['attribs'] );
                    }
                    $html .= Tag::_li();
                }
            }
            $html .=   Tag::_ul() .
                     Tag::_div();
        }
        $html .= Tag::_div();

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $jsLibraries .
                JS::javaScript( $js ) .
                $html;
    }

    public static function getMenuItems( $menuClasses = null ) {
        self::$log->trace( 'Entering ' . __METHOD__ );
        if ( $menuClasses == null ) {
            $menuClasses = Cfg::get( 'menu' );
        }

        $menuBlock = 0;
        $menuOptions = [];
        $resp = Response::factory();

        foreach ( $menuClasses as $key => $val ) {
            $resp->set( self::ACTIVE_MENU, $menuBlock ++ );
            $menuOptions[$key] = $val::menu( $resp );
        }
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $menuOptions;
    }

    public static function slugRedirect( $slug, $menuClasses = null ) {
        self::$log->trace( 'Entering ' . __METHOD__ );
        foreach ( self::getMenuItems( $menuClasses ) as $menuList ) {
            foreach ( $menuList as $row ) {
                if ( isset( $row['slug'] ) && $row['slug'] == $slug ) {
                    self::$log->trace( 'Exiting ' . __METHOD__ );
                    header( 'Location: ' . Cfg::siteUrl() . '/' . $row['url'] );
                    exit();
                }
            }
        }
        self::$log->trace( 'Exiting ' . __METHOD__ );

        // Default
        header( 'Location: ' . Cfg::siteUrl() );
        exit();
    }

    public static function responseObject() {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $resp = new Response( self::ACTIVE_MENU );
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $resp;
    }

}
