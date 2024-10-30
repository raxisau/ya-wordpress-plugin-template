<?php
namespace App\Controllers;

use \Jackbooted\Forms\Request;

class CommonAjaxEndpoints extends \Jackbooted\Html\WebPage {
    public static $ajaxEndpoints = [
        'TermsHtml' => __CLASS__ . '->termsHtml()',
    ];

    public function termsHtml() {
        $results = new \App\API\APIResult();

        // https://developer.wordpress.org/reference/classes/wp_query/
        $wpQuery = new \WP_Query( [ 'pagename' => Request::get( 'fldPostID', 'terms' ) ] );
        foreach( $wpQuery->get_posts() as $post ) {
            $results->set( 'post', $post->to_array() );
            break;
        }

        return $results->JSON();
    }

}
