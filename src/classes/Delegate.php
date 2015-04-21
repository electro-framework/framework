<?php
class Delegate {
    public $instance;
    protected $method;

    public function __construct($instance,$method) {
        if (!method_exists($instance,$method))
            throw new InvalidArgumentException("Method $method does not exist on the specified object.");
        $this->instance = $instance;
        $this->method = $method;
    }

    /**
     * Calls the method pointed at by the delegate.
     * Supports a variable length arguments list.
     */
    public function invoke() {
        $args = func_get_args();
        call_user_func_array(array($this->instance,$this->method),$args);
    }
}
