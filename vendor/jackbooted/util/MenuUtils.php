<?php

namespace Jackbooted\Util;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;

/*
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 */

/**
 */
class MenuUtils extends WebPage {

    const ACTIVE_MENU = 'ACTIVE_MENU';

    public static function display( $menuClasses = null ) {
        $id = 'MenuUtils_display' . Invocation::next();
        $jsLibraries = JS::libraryWithDependancies( JS::JQUERY_UI );
        $activeMenu = Request::get( self::ACTIVE_MENU, 0 );
        $js = <<<JS
            jQuery().ready ( function () {
                jQuery( '#$id' ).show()
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

        return  $jsLibraries .
                JS::javaScript( $js ) .
                $html;
    }

    public static function getMenuItems( $menuClasses = null ) {
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
        return $menuOptions;
    }

    public static function slugRedirect( $slug, $menuClasses = null ) {
        foreach ( self::getMenuItems( $menuClasses ) as $menuList ) {
            foreach ( $menuList as $row ) {
                if ( isset( $row['slug'] ) && $row['slug'] == $slug ) {
                    header( 'Location: ' . Cfg::siteUrl() . '/' . $row['url'] );
                    exit();
                }
            }
        }

        // Default
        header( 'Location: ' . Cfg::siteUrl() );
        exit();
    }

    public static function responseObject() {
        $resp = new Response( self::ACTIVE_MENU );
        return $resp;
    }

}
