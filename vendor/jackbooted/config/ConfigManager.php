<?php

namespace Jackbooted\Config;

use \Jackbooted\Html\WebPage;
use \Jackbooted\Util\MenuUtils;
use \Jackbooted\Forms\Request;
use \Jackbooted\Html\Widget;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Lists;
use \Jackbooted\DB\DB;

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
class ConfigManager extends WebPage {

    const DEF = '\Jackbooted\Config\ConfigManager->index()';

    public function index() {
        $html = '<h2 title="You are able to edit all your defaults ***WARNING*** please know what you are doing">JSON Configuration Editor</h2>';

        if ( ( $currentConfigKey = Request::get( 'fldCfgKey' ) ) == '' ) {
            $currentConfigKey = DB::oneValue( DB::DEF, 'SELECT fldKey FROM tblConfig ORDER BY 1 LIMIT 1' );
        }
        if ( $currentConfigKey === false || $currentConfigKey == '' ) {
            return $html .
                    'No Configuration available yet';
        }

        $html .= Tag::table( [ 'border' => '0', 'height' => '100%', 'width' => '100%' ] ) .
                   Tag::tr() .
                     Tag::td( [ 'nowrap' => 'nowrap', 'valign' => 'top' ] ) .
                       $this->editConfigForm( $currentConfigKey ) .
                     Tag::_td() .
                     Tag::td( [ 'width' => '100%', 'valign' => 'top' ] ) .
                       $this->editJSONEditForm( $currentConfigKey ) .
                     Tag::_td() .
                   Tag::_tr() .
                   Tag::tr() .
                     Tag::td( [ 'nowrap' => 'nowrap', 'valign' => 'top', 'colspan' => 2 ] ) .
                       $this->addForm() .
                     Tag::_td() .
                   Tag::_tr() .
                 Tag::_table();

        return $html;
    }

    public function addForm() {
        $resp = MenuUtils::responseObject();
        $html = Tag::form() .
                  $resp->action( __CLASS__ . '->addFormSave()' )->toHidden() .
                  Tag::table() .
                    Tag::tr() .
                      Tag::td() . 'New Key' . Tag::_td() .
                      Tag::td() . Tag::text( 'fldCfgKey' ) . Tag::_td() .
                      Tag::td() . Tag::submit( 'Add New Key' ) . Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form();
        return $html;
    }

    public function addFormSave() {
        Config::put( Request::get( 'fldCfgKey' ), true );

        return Widget::popupWrapper( 'Saved Config Item: ' . Request::get( 'fldCfgKey' ), 1000, 'Save Config Message' ) .
               $this->index();
    }

    public function editConfigForm( $currentConfigKey ) {
        return JS::library( JS::JQUERY ) .
               JS::javaScript( "jQuery().ready( function(){ jQuery('#fldCfgKey').focus (); });" ) .
               Tag::hTag( 'b' ) . 'Config Keys' . Tag::_hTag( 'b' ) .
               Tag::form( [ 'method' => 'get' ] ) .
                 MenuUtils::responseObject()->action( self::DEF )->toHidden( false ) .
                 Lists::select( 'fldCfgKey', 'SELECT DISTINCT fldKey FROM tblConfig ORDER BY 1', [
                    'style' => 'height: 100%',
                    'default' => $currentConfigKey,
                    'size' => 26,
                    'id' => 'fldCfgKey',
                    'onChange' => 'submit();'
                 ]) .
               Tag::_form() .
               '<br/>' .
               Tag::hRef( '?' . MenuUtils::responseObject()->action( __CLASS__ . '->reload()' )->toUrl(), 'Reload Config', [
                   'title' => 'reloads the configuration',
                   'onClick' => 'return confirm("Are You Sure you want to reload all configuration?")'
               ]);
    }

    public function editJSONEditForm( $currentConfigKey ) {
        $json = json_encode( Config::get( $currentConfigKey ) );
        $js = <<< JS
            var json = $json;
            main.load(json);
            main.resize();
            var pageDirty = false;
            jQuery().ready(function() {
                window.onbeforeunload = function () {
                    if ( ! pageDirty ) return;
                    return 'Changes have been made on this page and will be discarded.'
                };
                jQuery('textarea.jsonformatter-textarea').change( function() {
                    pageDirty = true;
                });
                jQuery('div.jsoneditor-value').change( function() {
                    pageDirty = true;
                });
                jQuery('#fldCfgValue').val( editor.get() );
            });

            function submitClicked () {
                var button = jQuery('#editJSONEditFormButton').attr('disabled',true);

                var orig = jQuery('#fldCfgValue').val();
                var ed   = editor.get();    // JSON Editor, RHS fields labels
                var form = formatter.get(); // Formatted Text Editor LHS

                if ( orig == ed && orig == form ) {
                    pageDirty = false;
                    alert( 'No changes, nothing to save' );
                    button.attr('disabled',false );
                    return false;
                }
                else if ( orig != ed && orig == form ) {
                    if ( confirm( 'Looks like you have made changes in the JSON Editor on right pane and not copied to Formatted Text Editor on left pane. Ok to save changes?' ) ) {
                        pageDirty = false;
                        jQuery('#fldCfgValue').val( ed );
                        jQuery('#editJSONEditForm').submit();
                        return true;
                    }
                    else {
                        button.attr('disabled',false );
                        return false;
                    }
                }
                else if ( orig == ed && orig != form ) {
                    if ( confirm( 'Looks like you have made changes in the Formatted Text Editor on left pane and not copied to the JSON Editor on right pane. Ok to save?' ) ) {
                        pageDirty = false;
                        jQuery('#fldCfgValue').val( form );
                        jQuery('#editJSONEditForm').submit();
                        return true;
                    }
                    else {
                        button.attr('disabled',false );
                        return false;
                    }
                }
                else if ( orig != ed && orig != form ) {
                    if ( ed == form ) {
                        pageDirty = false;
                        jQuery('#fldCfgValue').val( ed );
                        jQuery('#editJSONEditForm').submit();
                        return true;
                    }
                    else {
                        alert( 'You have changed both the JSON Editor on right pane and the Formatted Text Editor on left pane and they are inconsistent. ' +
                               'Please press one of the arrow buttons to fix and re-submit' );
                        button.attr('disabled',false );
                        return false;
                    }
                }
            }

            jQuery('body')
                .on( 'focus', '[contenteditable]', function() {
                    var t = jQuery(this);
                    t.data('before', t.html());
                    return t;
                })
                .on( 'blur keyup paste input', '[contenteditable]', function() {
                    var t = jQuery(this);
                    if (t.data('before') !== t.html() ) {
                        t.data('before', t.html());
                        t.trigger('change');
                    }
                    return t;
                });
        JS;
        return JS::library( JS::JQUERY ) .
               JS::library( 'jsoneditor.css' ) .
               JS::library( 'interface.css' ) .
               JS::library( 'jsoneditor.js' ) .
               JS::library( 'interface.js' ) .
               Tag::div( [ 'id' => 'auto' ] ) .
                 Tag::div( [ 'id' => 'contents', 'height' => '100%' ] ) .
                   Tag::table( [ 'border' => '0', 'height' => '100%', 'width' => '100%' ] ) .
                     Tag::tr() .
                       Tag::td( [ 'valign' => 'top', 'width' => '45%', 'height' => '100%' ] ) .
                         Tag::hTag( 'b' ) . '&nbsp;&nbsp;&nbsp;&nbsp;Formatted Text Editor' . Tag::_hTag( 'b' ) .
                         Tag::div( [ 'id' => 'jsonformatter' ] ) . Tag::_div() .
                       Tag::_td() .
                       Tag::td( [ 'valign' => 'top', 'width' => '10%', 'align' => 'center' ] ) .
                         Tag::div( [ 'id' => 'splitter' ] ) . Tag::_div() .
                       Tag::_td() .
                       Tag::td( [ 'valign' => 'top', 'width' => '45%', 'height' => '100%' ] ) .
                         Tag::hTag( 'b' ) . '&nbsp;&nbsp;&nbsp;&nbsp;JSON Editor' . Tag::_hTag( 'b' ) .
                         Tag::div( [ 'id' => 'jsoneditor' ] ) . Tag::_div() .
                       Tag::_td() .
                     Tag::_tr() .
                   Tag::_table() .
                 Tag::_div() .
               Tag::_div() .
               Tag::form( [ 'id' => 'editJSONEditForm' ], false ) . // No doubleclick protection. will handle it ourselves
                 MenuUtils::responseObject()->set( 'fldCfgKey', $currentConfigKey )->action( __CLASS__ . '->saveConfig()' )->toHidden() .
                 Tag::textArea( 'fldCfgValue', '', [ 'id' => 'fldCfgValue', 'style' => 'display: none;' ] ) .
                 '<b>Currently editing : <i>' . $currentConfigKey . '</i></b> ' .
                 Tag::button( 'Save', [ 'onClick' => 'submitClicked();', 'id' => 'editJSONEditFormButton' ] ) .
               Tag::_form() .
               JS::javaScript( $js );
    }

    public function saveConfig() {
        Config::put( Request::get( 'fldCfgKey' ), Request::get( 'fldCfgValue' ) );

        return Widget::popupWrapper( 'Saved Config Item: ' . Request::get( 'fldCfgKey' ), 1000, 'Save Config Message' ) .
               $this->index();
    }

    public function reload() {
        Config::clearCache();
        Widget::popupWrapper( 'Sucessfully reloaded the config', 1000, 'Reload' ) . $this->index();
    }
}
