<?php

namespace Jackbooted\Html;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Util\Invocation;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
class Widget extends \Jackbooted\Util\JB {

    private static $buttonDomSelectorList = [];

    public static function button( $domSelector ) {

        // If we have done this selector, then nothing to do
        if ( in_array( $domSelector, self::$buttonDomSelectorList ) ) {
            return '';
        }

        self::$buttonDomSelectorList[] = $domSelector;

        $js = <<<JS
            jQuery().ready ( function () {
                jQuery("$domSelector").button();
            });
        JS;
        return JS::libraryWithDependancies( JS::JQUERY_UI ) .
                JS::javaScript( $js );
    }

    private static $styleComboInvocations = 0;

    public static function comboBoxJS( $tag, $pickList ) {
        if ( !isset( $pickList ) ||
                $pickList === false ||
                !is_array( $pickList ) ||
                count( $pickList ) <= 0 ) {
            return '';
        }

        $html = '';
        if ( self::$styleComboInvocations == 0 ) {
            self::$styleComboInvocations ++;
            // .ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text
            $css = <<<CSS
                .ui-button-icon-only .ui-button-text {
                    padding: .0em;
                }
                .ui-widget {
                    font-size: 1.0em;
                }
            CSS;
            $html .= JS::css( $css );
        }

        $maxLength = max( array_map( 'strlen', $pickList ) );
        if ( $maxLength > 20 ) {
            $maxLength = 20;
        }

        $pickListJSON = json_encode( $pickList );
        $js = <<<JS
            var isOpen = false;
            jQuery().ready ( function () {
                jQuery("$tag")
                    .autocomplete({
                        minLength: 0,
                        open: function(event, ui) { isOpen = true; },
                        close: function(event, ui) { isOpen = false; },
                        source: $pickListJSON
                    })
                    .attr( 'size', $maxLength );
                jQuery('<button type="button">&nbsp;</button>')
                    .attr( 'tabIndex', -1 )
                    .attr( 'title', 'Show All Items' )
                    .button({
                        icons: { primary: 'ui-icon-triangle-1-s' },
                        text: false
                    })
                    .removeClass( 'ui-corner-all' )
                    .addClass( 'ui-corner-right ui-button-icon' )
                    .insertAfter ( jQuery("$tag") )
                    .click(function() {
                        if ( isOpen ) {
                            jQuery('$tag').autocomplete('close');
                        }
                        else {
                            jQuery('$tag').autocomplete('search','').focus();
                        }
                    });
            });
        JS;
        return JS::libraryWithDependancies( JS::JQUERY_UI ) .
                $html .
                JS::javaScript( $js );
    }

    public static function tinyMCE( $selector, $apikey='no-api-key', $height='' ) {
        if ( $apikey == 'no-api-key' ) {
            $apikey = Cfg::get('tinymce_api', 'no-api-key');
        }

        if ( $apikey == '' || $apikey == null ) {
            $apikey = 'no-api-key';
        }

        $mceHeight = ( $height == '' ) ? '' : "height: {$height},";

        $js = <<<JS
            jQuery().ready(function() {
                tinymce.init({
                    {$mceHeight}
                    selector: '{$selector}',
                    theme: 'silver',
                    plugins: [
                      'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                      'searchreplace wordcount visualblocks visualchars code fullscreen',
                      'insertdatetime media nonbreaking save table contextmenu directionality',
                      'emoticons template paste textcolor colorpicker textpattern imagetools'
                    ],
                    toolbar1: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                    image_advtab: true,
                    convert_urls: false
                });
            });
        JS;

        return JS::library ( JS::JQUERY ) .
               "<script src='//cdn.tiny.cloud/1/$apikey/tinymce/5/tinymce.min.js' referrerpolicy='origin'></script>" .
               JS::javaScript ( $js );
    }

    public static function popupWrapper( $msg, $timeout = 1500, $title = '' ) {
        $id = Invocation::next();
        if ( $title == '' ) {
            $title = 'Message:';
        }

        if ( $timeout < 0 ) {
            $timeoutJS = <<<JS
                modal: true,
            JS;
        }
        else {
            $timeoutJS = <<<JS
                modal: false,
                open: function(event, ui) {
                    setTimeout ( "jQuery('#popupWrapper_$id').dialog('close')", $timeout );
                },
            JS;
        }

        $js = <<<JS
            jQuery().ready(function() {
                jQuery('<div id="popupWrapper_$id" title="$title">$msg</div>' )
                    .dialog({
                        $timeoutJS
                        hide: 'fade',
                        position: { at: "top+200" }
                    });
            });
        JS;

        return JS::libraryWithDependancies( JS::JQUERY_UI ) .
                JS::javaScript( $js );
    }

    private static $styleTableInvocations = 0;

    public static function styleTable( $selector ) {
        if ( self::$styleTableInvocations == 0 ) {
            self::$styleTableInvocations ++;
            $js = <<<JS
                (function ($) {
                    $.fn.styleTable = function (options) {
                        var defaults = {
                            css: 'styleTable'
                        };
                        options = $.extend(defaults, options);

                        return this.each(function () {

                            input = jQuery(this);
                            input.addClass(options.css);

                            input.find("tr").on('mouseover mouseout', function (event) {
                                if (event.type == 'mouseover') {
                                    jQuery(this).children("td").addClass("ui-state-hover");
                                } else {
                                    jQuery(this).children("td").removeClass("ui-state-hover");
                                }
                            });

                            input.find("th").addClass("ui-state-default");
                            input.find("td").addClass("ui-widget-content");

                            input.find("tr").each(function () {
                                jQuery(this).children("td:not(:first)").addClass("first");
                                jQuery(this).children("th:not(:first)").addClass("first");
                            });
                        });
                    };
                })(jQuery);
            JS;
            $css = <<<CSS
                .styleTable { border-collapse: separate; }
                .styleTable TD { font-weight: normal !important; padding: .3em; border-top-width: 0px !important; }
                .styleTable TH { text-align: center; padding: .5em .3em; }
                .styleTable TD.first, .styleTable TH.first { border-left-width: 0px !important; }
            CSS;
            $html = JS::library( JS::JQUERY_UI_CSS ) .
                    JS::css( $css ) .
                    JS::libraryWithDependancies( JS::JQUERY ) .
                    JS::javaScript( $js );
        }
        else {
            $html = '';
        }
        $js = <<<JS
            jQuery().ready(function () {
                jQuery('$selector').styleTable();
            });
        JS;
        return $html .
               JS::javaScript( $js );
    }

    private static $datePickerJSDisplayed = false;

    public static function datePickerJS( $selector = 'input.datepicker', $format='yy-mm-dd' ) {
        if ( self::$datePickerJSDisplayed ) {
            return '';
        }
        self::$datePickerJSDisplayed = true;

        /* The format can be combinations of the following:
            d - day of month (no leading zero)
            dd - day of month (two digit)
            o - day of year (no leading zeros)
            oo - day of year (three digit)
            D - day name short
            DD - day name long
            m - month of year (no leading zero)
            mm - month of year (two digit)
            M - month name short
            MM - month name long
            y - year (two digit)
            yy - year (four digit)
            @ - Unix timestamp (ms since 01/01/1970)
            ! - Windows ticks (100ns since 01/01/0001)
            '...' - literal text
            '' - single quote
            anything else - literal text
         */

        $js = <<<JS
            jQuery().ready(function() {
                jQuery( "$selector" ).each( function() {
                    jQuery(this).datepicker({
                        dateFormat: "$format"
                    });
                });
            });
        JS;
        return JS::libraryWithDependancies( JS::JQUERY_UI ) .
                JS::javaScript( $js );
    }

    public static function facebox( $selector = 'a.facebox' ) {
        $jsUrl = Cfg::get( 'js_url' );
        $js = <<<JS
            jQuery().ready(function() {
                jQuery('$selector').facebox({ closeImage:   '$jsUrl/images/closelabel.png',
                                         loadingImage: '$jsUrl/images/loading.gif'

                });
            });
        JS;
        return JS::libraryWithDependancies( JS::FACEBOX ) .
               JS::javaScript( $js );
    }

    public static function reload( $callBack, $url, $numOfSeconds = 20, $css = 'ReloadWidget' ) {
        $id = '_' . Invocation::next();

        $js = <<<JS
            var countDownInterval{$id} = {$numOfSeconds}; //configure refresh interval (in seconds)
            var countDownTime{$id} = countDownInterval{$id} + 1;
            var reloadTimes{$id} = 0;
            var counter{$id};

            function countDown{$id}(){
                countDownTime{$id}--;
                jQuery('#stop{$id}').show();
                jQuery('#start{$id}').hide();
                if ( countDownTime{$id} <= 0 ) {
                    clearTimeout(counter{$id});
                    updateReloadArea{$id}();
                    return;
                }
                jQuery('#countDownText{$id}').html( countDownTime{$id} + '' );
                counter{$id} = setTimeout( "countDown{$id}()", 1000 );
            }

            function stopCount{$id}(){
                clearTimeout(counter{$id})
                jQuery('#stop{$id}').hide();
                jQuery('#start{$id}').show();
            }

            function updateReloadArea{$id}(){
                countDownTime{$id} = countDownInterval{$id} + 1;
                reloadTimes{$id} = reloadTimes{$id} + 1;
                jQuery('#reload{$id}').load('{$url}&R='+reloadTimes{$id}, function() {
                    countDown{$id} ();
                });
            }

            jQuery().ready ( function () {
                countDown{$id} ();
            });
        JS;

        $html = 'Next ' .
                Tag::hRef( "javascript:countDownTime{$id}=0", 'refresh', [ 'title' => 'Click here to refresh now.', 'class' => $css ] ) .
                ' in ' .
                Tag::hTag( 'span', [ 'id' => "countDownText{$id}", 'class' => $css ] ) .
                $numOfSeconds .
                Tag::_hTag( 'span' ) .
                ' seconds ' .
                Tag::hRef( "javascript:stopCount{$id}()", 'Stop', [ 'id' => "stop{$id}", 'title' => 'Click here to stop the timer.','class' => $css ] ) .
                Tag::hRef( "javascript:countDown{$id}()", 'Start', [ 'id' => "start{$id}",'title' => 'Click here to start the timer.','class' => $css ] ) .
                '<br/>' .
                Tag::div( [ 'id' => "reload{$id}",'class' => $css ] ) .
                    call_user_func( $callBack ) .
                Tag::_div();

        return JS::library( JS::JQUERY ) .
                JS::javaScript( $js ) .
                $html;
    }
}
