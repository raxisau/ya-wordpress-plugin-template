<?php

namespace Jackbooted\Cron;

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
/*
 * There are a few Cron Parser classes (about 3)
 * All were way to big for what was needed. This is an attempt to simplify the process
 * Not the most optimised
 *
 * For this to be useful need to keep track of the last time that the command was run.
 * This means that you can use a construct like this
 *
 *      foreach ( self::getCronTabList () as $sheduleItem ) {
 *
 *          $storedLastRunTime = strtotime( ( $sheduleItem->lastRun == '' ) ? $sheduleItem->start : $sheduleItem->lastRun );
 *          $previousCalculatedRunTime = CronParser::lastRun( $sheduleItem->cron );
 *
 *          // This looks at when the item had run. If the stored value is less than
 *          // the calculated value means that we have past a run period. So need to run
 *          if ( $storedLastRunTime <  $previousCalculatedRunTime ) {
 *
 *              // Update the run time to now
 *              $sheduleItem->lastRun = date( 'Y-m-d H:i', $previousCalculatedRunTime );
 *              $sheduleItem->save ();
 *
 *              // Do the work to run the cron job here
 *              // ..................
 *          }
 *      }
 *
 * Cron Definition
  # * * * * *
  # - - - - -
  # | | | | |
  # | | | | |
  # | | | | +----- day of week (0 - 6) (0 to 6 are Sunday to Saturday, or use names; 7 is Sunday, the same as 0)
  # | | | +---------- month (1 - 12)
  # | | +--------------- day of month (1 - 31)
  # | +-------------------- hour (0 - 23)
  # +------------------------- min (0 - 59)
 *
 * See the code at the end of this class to see some examples
 *
 */
class CronParser extends \Jackbooted\Util\JB {

    /**
     * Gets the last run date before now
     * @param String $cronString
     * @return number time in seconds that the last cron task ran
     */
    public static function lastRun( $cronString ) {
        $originalString = $cronString;
        $cronString = strtolower( $cronString );

        $mappings = [
            '@yearly'   => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly'  => '0 0 1 * *',
            '@weekly'   => '0 0 * * 0',
            '@daily'    => '0 0 * * *',
            '@hourly'   => '0 * * * *'
        ];
        if ( isset( $mappings[$cronString] ) ) {
            $cronString = $mappings[$cronString];
        }

        // recognise the really simple case
        if ( $cronString == '* * * * *' ) {
            $calcTime = ( (int) ( time() / 60 ) ) * 60;
            //echo "Every Minute\n";
            return $calcTime;
        }

        // Get rid of names
        $cronString = str_replace( [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ], [ '0', '1', '2', '3', '4', '5', '6' ], $cronString );
        $cronString = str_replace( [ 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ], [ '0', '1', '2', '3', '4', '5', '6' ], $cronString );

        // Get the parts if not 5 return every minute
        $cronParts = preg_split( '/\s+/', $cronString, -1, PREG_SPLIT_NO_EMPTY );
        if ( count( $cronParts ) != 5 ) {
            $calcTime = ( (int) ( time() / 60 ) ) * 60;
            //echo "Not 5 parts\n";
            return $calcTime;
        }

        // evaluate the valid ranges for all of the parts
        $cronParts[0] = self::cronPartToRange( $cronParts[0], range( 0, 59 ) ); // Minutes
        $cronParts[1] = self::cronPartToRange( $cronParts[1], range( 0, 23 ) ); // Hours
        $cronParts[2] = self::cronPartToRange( $cronParts[2], range( 1, 31 ) ); // Day of Month
        $cronParts[3] = self::cronPartToRange( $cronParts[3], range( 1, 12 ) ); // Month of the year
        $cronParts[4] = self::cronPartToRange( $cronParts[4], range( 0, 6 ) );  // Day of the week
        $cronParts[5] = [ (int) date( 'Y' ) - 2, (int) date( 'Y' ) - 1, (int) date( 'Y' ) ]; // only go back 2 years
        // ******* TODO ****************
        // The code below is usually commented out.
        // Do not need this in production. So when you are done, remove
        //echo '<br/>' . "\n" . 'Original: ' . $originalString . ' Optimised: ' . $cronString . '<br/>' . "\n";
        //$cols = [ 'min', 'hrs', 'day or month', 'month', 'day of week', 'year'];
        //foreach ( $cronParts as $idx => $part ) {
        //    echo $idx . ' - ' . $cols[$idx] . ' - [' . join( ', ', $part ) . ']<br/>' . "\n";
        //}
        // Find the index for the last run based on current time
        $correctParts = [ 0, 0, 0, 0, 0, 0 ];
        $numDaysOfWeek = count( $cronParts[4] );
        $now = time();

        // search down the to the last run
        for ( $correctParts[5] = count( $cronParts[5] ) - 1; $correctParts[5] >= 0; $correctParts[5] -- ) {

            for ( $correctParts[3] = count( $cronParts[3] ) - 1; $correctParts[3] >= 0; $correctParts[3] -- ) {
                for ( $correctParts[2] = count( $cronParts[2] ) - 1; $correctParts[2] >= 0; $correctParts[2] -- ) {

                    // Screen out date where date is > 29 for feb
                    if ( $cronParts[3][$correctParts[3]] == 2 && $cronParts[2][$correctParts[2]] > 29 ) {
                        //echo 'Skipping : Month: ' . $cronParts[3][$correctParts[3]] . ' Day: ' . $cronParts[2][$correctParts[2]] . '<br/>' . "\n";
                        continue;
                    }

                    // If this is the 31 and month is Sep, Apr, Jun, Nov then skip
                    if ( $cronParts[2][$correctParts[2]] == 31 && in_array( $cronParts[3][$correctParts[3]], [ 9, 4, 6, 11 ] ) ) {
                        //echo 'Skipping : Month: ' . $cronParts[3][$correctParts[3]] . ' Day: ' . $cronParts[2][$correctParts[2]] . '<br/>' . "\n";
                        continue;
                    }

                    // If this is not a valid day then skip it
                    if ( $numDaysOfWeek != 7 ) {
                        $dow = self::getDayOfWeek( $cronParts, $correctParts );
                        if ( !in_array( $dow, $cronParts[4] ) ) {
                            //echo 'Skipping : Day of Week: ' . $dow . '<br/>' . "\n";
                            continue;
                        }
                    }

                    // Calc hour and minute
                    for ( $correctParts[1] = count( $cronParts[1] ) - 1; $correctParts[1] >= 0; $correctParts[1] -- ) {
                        for ( $correctParts[0] = count( $cronParts[0] ) - 1; $correctParts[0] >= 0; $correctParts[0] -- ) {
                            $calcTime = self::calcTime( $cronParts, $correctParts );
                            if ( $calcTime < $now ) {
                                return $calcTime;
                            }
                        }
                    }
                }
            }
        }

        // Made it to here then return stanadrd time
        return ( (int) ( time() / 60 ) ) * 60;
    }

    /**
     * Calculates the time based on the pointers into the valid range of array.
     *
     * @param array $cronParts array of arrays containing the valid ranges based on cron string
     * @param array $correctParts pointers into the array that we are testing
     * @return number The time based on the valid cron pieces
     */
    private static function calcTime( $cronParts, $correctParts ) {
        $tim = mktime( $cronParts[1][$correctParts[1]], $cronParts[0][$correctParts[0]], 0, $cronParts[3][$correctParts[3]], $cronParts[2][$correctParts[2]], $cronParts[5][$correctParts[5]] );
        //echo '$cronParts  - ' . json_encode( $cronParts ) . '<br/>' . "\n";
        //echo '$correctParts  - [' . join( ', ', $correctParts  ) . ']<br/>' . "\n";
        //echo date ( 'Y-m-d H:i', $tim ) . '<br/>' . "\n";
        return $tim;
    }

    /**
     * Calculates the day of the week based on the ranges and pointers into the current
     * @param unknown $cronParts
     * @param unknown $correctParts
     * @return number day of week 0-6
     */
    private static function getDayOfWeek( $cronParts, $correctParts ) {
        $tim = mktime( 0, 0, 0, $cronParts[3][$correctParts[3]], $cronParts[2][$correctParts[2]], $cronParts[5][$correctParts[5]] );
        return (int) date( 'w', $tim );
    }

    /**
     * Takes the cron component and the range of valid numbers and reduces the list.
     * This method calls itself recursively until all the elements are evaluated
     *
     * @param type $part
     * @param type $fullRange. For the position the $fullRange variable contains all the elements that would be valid.
     *                         e.g. minutes would be range( 0, 59 )
     * @return type
     */
    private static function cronPartToRange( $part, $fullRange ) {

        // simple digit so just return
        if ( preg_match( '/^[0-9]+$/', $part ) ) {
            return [ (int) $part ];
        }

        // If there are commas then multiple components.
        // get all the components and then merge, sort and uniq
        else if ( strpos( $part, ',' ) !== false ) {
            $validValues = [];
            foreach ( explode( ',', $part ) as $bit ) {
                $validValues = array_merge( $validValues, self::cronPartToRange( $bit, $fullRange ) );
            }
            $validValues = array_unique( $validValues );
            sort( $validValues );
            return $validValues;
        }

        // This is the starting point and the divisor e.g.
        // */5 = start at 0 and select every 5th element so 0, 5, 10 ....
        // or 2/3 = start at second element and then every 3rd element so 2, 5,
        // LHS could be a range like 30-50
        else if ( strpos( $part, '/' ) !== false ) {
            //Split to LHS and RHS
            $bits = explode( '/', $part );

            // recursivley get the range of LHS
            $lhsRange = self::cronPartToRange( $bits[0], $fullRange );

            // If there is no elements then some failure and should not happen default to all
            if ( count( $lhsRange ) == 0 ) {
                $lhsRange = $fullRange;
            }

            // If this has one element then it must be starting point for the
            // so something like 5/15 => 5, 20, 35, 50
            // Take off the full range of elements till you get to the matching one
            else if ( count( $lhsRange ) == 1 ) {
                while ( count( $fullRange ) > 0 && $fullRange[0] != $lhsRange[0] ) {
                    array_shift( $fullRange );
                }
                $lhsRange = $fullRange;
            }

            $rhsElement = (int) $bits[1];

            // Identify the positions that match the RHS. Step through the array and see if the indexes
            // modulus rhs is 0
            $validValues = [];
            foreach ( $lhsRange as $idx => $num ) {
                if ( ( $idx % $rhsElement ) == 0 )
                    $validValues[] = $num;
            }
            return $validValues;
        }

        // Must be a range, so split LHS and RHS to get the valid range
        else if ( strpos( $part, '-' ) !== false ) {
            $bits = explode( '-', $part );
            return range( (int) $bits[0], (int) $bits[1] );
        }

        // Default to full range
        else {
            return $fullRange;
        }
    }

}

// The code below is usually commented out.
// Do not need this in production. So when you are done, remove
//date_default_timezone_set ( 'Australia/Brisbane' );
//echo 'Simple case - no calcs (* * * * *) - LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '* * * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '5/15 0 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0/15 * * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 2 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '* 11-20 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0/15 * * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '* 12-21 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 2 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '1-59/2 14-23 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0-58/2 14-23 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '* * * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '* 22-23 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '1-2 22-23 * * Monday-Wednesday' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '1,2,3,4,10-20 11-20 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 */4 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 0/4 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 2/3 * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '30-50/3 * * * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 0 31 * *' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '0 0 31 * Monday,Wednesday,Friday' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '02-59/15 02-04 * * Thu' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '01 00 * * Tue' ) ) . '<br/>' . "\n";
//echo 'LastRun Date: ' . date ( 'Y-m-d H:i', CronParser::lastRun( '02 00 * * Thu' ) ) . '<br/>' . "\n";
//exit;
