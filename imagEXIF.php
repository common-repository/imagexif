<?php
/*
Plugin Name: ImagEXIF
Plugin URI: http://plugins.svn.wordpress.org/imagexif/
Description: Populate image description with EXIF data
Depends: PHP EXIF Module
Version: 0.1
Author: Iacami
Author URI: http://www.grupolapis.com.br
 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/


register_activation_hook( __FILE__, 'imagexif_activate' );
//check if exif function is avaiable
function imagexif_activate(){
    if (!function_exists('exif_read_data'))
        die('EXIF Module not found');
}

//function collect_exif copied from http://wordpress.org/extend/plugins/display-exif/
function imagexif_collect_exif($filename) {
    $exif = @exif_read_data($filename, 'EXIF');
    $exif_array = null;
    if (!empty($exif)) {
        $exif_tags = array('Make', 'Model', 'DateTimeOriginal', 'ExposureProgram', 'ExposureTime', 'FNumber', 'ISOSpeedRatings', 'FocalLength', 'MeteringMode', 'LightSource', 'SensingMethod', 'ExposureMode', 'FileName', 'FileSize', 'Software', 'XResolution', 'YResolution', 'ExifVersion', 'title');
        foreach ($exif_tags as $key) {
            switch ($key) {
                case 'FocalLength':
                    $tmparray = explode('/', $exif[$key]);
                    if (count($tmparray) > 1)
                        $exif_array[$key] = ( $tmparray[0] / $tmparray[1] ) . 'mm';
                    else
                        $exif_array[$key] = ( $tmparray[0] ) . 'mm';
                    break;
                case 'FNumber':
                    $tmparray = explode('/', $exif[$key]);
                    if ($tmparray[0] < 3000 && $tmparray[0] > 0)
                        $exif_array[$key] = ( $tmparray[0] / $tmparray[1] );
                    break;
                case 'ExposureProgram':
                    $exposure_program_data = array(
                        1 => 'Manual',
                        2 => 'Normal Program',
                        3 => 'Aperture Priority',
                        4 => 'Shutter Priority',
                        5 => 'Creative Program',
                        6 => 'Action Program',
                        7 => 'Portrait Mode',
                        8 => 'Landscape Mode');
                    $exif_array[$key] = $exposure_program_data[intval($exif[$key])];
                    break;
                case 'MeteringMode':
                    $metering_mode_data = array(
                        0 => 'Unknown',
                        1 => 'Average',
                        2 => 'Center weighted average',
                        3 => 'Spot',
                        4 => 'Multi Spot',
                        5 => 'Pattern',
                        6 => 'Partial',
                        255 => 'Other');
                    $exif_array[$key] = $metering_mode_data[intval($exif[$key])];
                    break;
                case 'LightSource':
                    $light_source_data = array(
                        0 => 'Unknown',
                        1 => 'Daylight',
                        2 => 'Fluorescent',
                        3 => 'Tungsten',
                        4 => 'Flash',
                        9 => 'Fine weather',
                        10 => 'Cloudy weather',
                        11 => 'Shade',
                        12 => 'Daylight fluorescent', 13 => 'Day white fluorescent', 14 => 'Cool white fluorescent', 15 => 'White fluorescent',
                        17 => 'Standard light A', 18 => 'Standard light B', 19 => 'Standard light C',
                        20 => 'D55', 21 => 'D65', 22 => 'D75', 23 => 'D50',
                        24 => 'ISO studio tungsten',
                        255 => 'Other light source');
                    $exif_array[$key] = $light_source_data[intval($exif[$key])];
                    break;
                case 'SensingMethod':
                    $sensing_method_data = array(
                        2 => 'One-chip color area sensor',
                        3 => 'Two-chip color area sensor',
                        4 => 'Three-chip color area sensor',
                        5 => 'Color sequential area sensor',
                        7 => 'Trilinear sensor',
                        8 => 'Color sequential linear sensor');
                    $exif_array[$key] = $sensing_method_data[intval($exif[$key])];
                    break;
                case 'ExposureMode':
                    $exposure_mode_data = array(
                        0 => 'Auto',
                        1 => 'Manual',
                        2 => 'Auto bracket');
                    $exif_array[$key] = $exposure_mode_data[intval($exif[$key])];
                    break;
                case 'ExifVersion':
                    $exif_array[$key] = floatval($exif[$key]) / 100 . '';
                    break;
                default:
                    $exif_array[$key] = $exif[$key];
                    break;
            } /* switch */
        } /* foreach */
    } /* if */
    return( $exif_array );
}

//add filter to attachment
//TODO: besides working, it seems to be the wrong hook - runs where shouldn't.
add_filter('attachment_fields_to_edit', 'imagexif_add_exifdata');

function imagexif_add_exifdata($file) {
//TODO: fix this str_replace to a safer way to find real file path
    $check = "../" . str_replace(get_bloginfo('wpurl'). "/", "", $file['image_url']['value']);
    if (file_exists($check)) {
        if ($exif_data = imagexif_collect_exif($check, $exif_tags)) {
            $file['post_content']['value'] = json_encode($exif_data);
        }
    } else {
        $file['post_content']['value'] = $check;
    }
    return $file;
}

