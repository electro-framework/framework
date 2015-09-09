<?php
namespace Selenia;


class Event {
    protected $observable;
    protected $delegates = array();

    public function __construct($owner) {
        $this->owner = $owner;
    }

    public function attach(Delegate $delegate) {
        $this->delegates[] = $delegate;
    }

    public function detach($instance) {
        foreach ($this->delegates as $k=>$v)
            if ($v->instance == $instance) unset($this->delegates[$k]);
    }

    public function fire(array $args = NULL) {
        foreach ($this->delegates as $delegate)
            $delegate->invoke($this->observable,$args);
    }

}
