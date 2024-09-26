<?php

namespace Jackbooted\Util;

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
class PDFUtil extends FPDF {

    private $pageNumber = 1;
    private $fontHeight = 10;

    public function __construct( $orientation = 'L', $unit = 'mm', $format = 'A4' ) {
        parent::__construct( $orientation, $unit, $format );
    }

    public function Header( $border = 0 ) {
        if ( $this->pageNumber == 1 ) {
            $this->SetXY( $this->lMargin, $this->tMargin );
            //$this->Image( './pic5.jpg');
        }
        if ( ( $border & 1 ) != 0 ) {
            // Horizontal
            $this->Line( $this->lMargin, $this->tMargin, $this->w - $this->rMargin, $this->tMargin );
            $this->Line( $this->lMargin, $this->h - $this->bMargin, $this->w - $this->rMargin, $this->h - $this->bMargin );
        }
        if ( ( $border & 2 ) != 0 ) {
            // Vertical
            $this->Line( $this->lMargin, $this->tMargin, $this->lMargin, $this->h - $this->bMargin );
            $this->Line( $this->w - $this->rMargin, $this->tMargin, $this->w - $this->rMargin, $this->h - $this->bMargin );
        }
    }

    public function Footer() {
        $oldFontSize = $this->FontSizePt;
        $this->SetFont( 'Arial', 'I', 8 );
        $this->SetY( $this->h - $this->bMargin + ( $this->FontSize / 2 ) + 1 );
        $this->Cell( 0, 0, "Page: $this->pageNumber/{nb}   ", 0, 0, 'R' );
        $this->SetFont( 'Arial', '', $oldFontSize );
        $this->pageNumber ++;
    }

    public function resetLocation() {
        $this->SetXY( $this->lMargin, $this->tMargin );
    }

    public function cross( $crossSize = 2 ) {
        $this->Line( $this->GetX() - $crossSize, $this->GetY() - $crossSize, $this->GetX() + $crossSize, $this->GetY() + $crossSize );
        $this->Line( $this->GetX() + $crossSize, $this->GetY() - $crossSize, $this->GetX() - $crossSize, $this->GetY() + $crossSize );
    }

    public function displayRow( $arr ) {
        $cellHeight = $this->FontSize + 1;

        if ( is_array( $arr ) ) {
            foreach ( $arr as $key => $value ) {
                $this->Cell( 0, $cellHeight, $key . ': ' . $value );
                $this->Ln();
            }
            $this->Ln();
        }
    }

    public function h3( $msg ) {
        $cellHeight = $this->FontSize + 1;

        $oldFontSize = $this->FontSizePt;
        $this->SetFont( 'Arial', 'B', 13 );
        $this->Cell( 0, $cellHeight, $msg );
        $this->Ln();
        $this->SetFont( 'Arial', '', $oldFontSize );
    }

    public function h4( $msg ) {
        $cellHeight = $this->FontSize + 1;

        $oldFontSize = $this->FontSizePt;
        $this->SetFont( 'Arial', 'B', 12 );
        $this->Cell( 0, $cellHeight, $msg );
        $this->Ln();
        $this->SetFont( 'Arial', '', $oldFontSize );
    }

    public function th( $msg, $cellWidth ) {
        $oldFontSize = $this->FontSizePt;

        $this->SetFont( 'Arial', 'B', $this->fontHeight );
        $cellHeight = $this->FontSize + 1;
        $this->Cell( $cellWidth, $cellHeight, $msg, 0, 0, 'C' );
        $this->Ln();
        $this->SetFont( 'Arial', '', $oldFontSize );
    }

    public function td( $msg, $cellWidth ) {
        $x = $this->GetX();
        $cellHeight = $this->FontSize + 1;
        $line = '';
        foreach ( explode( ' ', $msg ) as $idx => $word ) {
            if ( $idx == 0 ) {
                $line = $word;
            }
            else if ( $this->GetStringWidth( $line ) + $this->GetStringWidth( ' ' . $word ) + 2 > $cellWidth ) {
                $this->Cell( $cellWidth, $cellHeight, $line );
                $this->Ln();
                $this->SetX( $x );
                $line = $word;
            }
            else {
                $line .= ' ' . $word;
            }
        }
        $this->Cell( $cellWidth, $cellHeight, $line );
        $this->Ln();
    }

    public function drawTable( $tab, $widthList = null, $border = 3, $x = 0, $y = 0, $totalWidth = '100%' ) {
        // If this is just one row then convert to 2D array
        if ( !isset( $tab[0] ) || !is_array( $tab[0] ) ) {
            $tab = [ $tab ];
        }
        $rowCount = count( $tab );
        $colCount = count( $tab[0] );
        $colNames = array_keys( $tab[0] );

        if ( $x != 0 ) {
            $this->SetX( $x );
        }

        if ( $y != 0 ) {
            $this->SetY( $y );
        }

        $topY = $this->GetY();

        $rMargin = $this->w - $this->rMargin;
        $oneHundredPercent = $rMargin - $this->GetX();
        if ( is_string( $totalWidth ) ) {
            $totalWidth = $oneHundredPercent * $this->percentToDecimal( $totalWidth );
        }
        else if ( $totalWidth > $oneHundredPercent ) {
            $totalWidth = $oneHundredPercent;
        }

        if ( $widthList == null ) {
            $widthList = array_fill( 0, $colCount, ( 100.0 / $colCount ) . '%' );
        }

        // Calculate the tab stops
        $tabStops = [ $this->GetX() ];
        foreach ( $widthList as $idx => $w ) {
            if ( is_string( $w ) ) {
                $tabStops[] = $tabStops[$idx] + $oneHundredPercent * $this->percentToDecimal( $w );
            }
            else {
                $tabStops[] = $tabStops[$idx] + $w;
            }
        }

        $y = $topY = $this->GetY();

        if ( ( $border & 1 ) != 0 ) {
            // Line at top of table
            $this->Line( $tabStops[0], $topY, end( $tabStops ), $topY );
        }

        for ( $col = 0; $col < $colCount; $col++ ) {
            $this->SetXY( $tabStops[$col], $y );
            $colWidth = $tabStops[$col + 1] - $tabStops[$col];
            $this->th( $colNames[$col], $colWidth );
        }
        if ( ( $border & 1 ) != 0 ) {
            // Line at bottom of headers
            $this->Line( $tabStops[0], $this->GetY(), end( $tabStops ), $this->GetY() );
        }

        if ( ( $border & 2 ) != 0 ) {
            foreach ( $tabStops as $tabS ) {
                $this->Line( $tabS, $topY, $tabS, $this->GetY() );
            }
        }

        $nextRow = $this->GetY();
        for ( $row = 0; $row < $rowCount; $row++ ) {
            $y = $nextRow;

            $rowSize = 0;
            for ( $col = 0; $col < $colCount; $col++ ) {
                $cellHt = $this->cellHeight( $tab[$row][$colNames[$col]], $colWidth );
                if ( $cellHt > $rowSize ) {
                    $rowSize = $cellHt;
                }
            }
            if ( $y + $rowSize > ( $this->h - $this->bMargin ) ) {
                $this->AddPage();
                $nextRow = $y = $this->GetY();
                if ( ( $border & 1 ) != 0 ) {
                    $this->Line( $tabStops[0], $y, end( $tabStops ), $y );
                }
            }

            for ( $col = 0; $col < $colCount; $col++ ) {
                $this->SetXY( $tabStops[$col], $y );
                $colWidth = $tabStops[$col + 1] - $tabStops[$col];
                $this->td( $tab[$row][$colNames[$col]], $colWidth );
                if ( $nextRow < $this->GetY() ) {
                    $nextRow = $this->GetY();
                }
            }
            if ( ( $border & 1 ) != 0 ) {
                $this->Line( $tabStops[0], $nextRow, end( $tabStops ), $nextRow );
            }
            if ( ( $border & 2 ) != 0 ) {
                foreach ( $tabStops as $tabS ) {
                    $this->Line( $tabS, $y, $tabS, $nextRow );
                }
            }
        }
        $this->SetXY( $tabStops[0], $nextRow );
    }

    private function percentToDecimal( $perc ) {
        return floatval( trim( str_replace( '%', '', $perc ) ) ) / 100.0;
    }

    private function cellHeight( $msg, $cellWidth ) {
        $cellHt = 0;
        $line = '';
        foreach ( explode( ' ', $msg ) as $idx => $word ) {
            if ( $idx == 0 ) {
                $line = $word;
            }
            else if ( $this->GetStringWidth( $line ) + $this->GetStringWidth( ' ' . $word ) + 2 > $cellWidth ) {
                $cellHt += $this->lasth;
                $line = $word;
            }
            else {
                $line .= ' ' . $word;
            }
        }
        $cellHt += $this->lasth;

        return $cellHt;
    }

}
