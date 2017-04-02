<?php
/**
 * Created by PhpStorm.
 * User: employee
 * Date: 4/2/17
 * Time: 2:48 PM
 * A little scratch pad. Delete from final.
 */

DB::listen( function ( $sql ) {
    print_r( $sql->sql );
    print_r( $sql->bindings );
} );