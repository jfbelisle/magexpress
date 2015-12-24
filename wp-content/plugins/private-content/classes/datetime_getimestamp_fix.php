<?php
// geTimestamp method fix for PHP versions < 5.3

class pc_DateTime extends DateTime {
    public function getTimestamp() {
        return $this->format( 'U' );
    }
}
