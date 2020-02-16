<?php
namespace sirjasongo\atk4m2m;

trait ManyToMany
{
    private $_m2m = [];

    public function hasManyToMany($target_class, $bridge_class, $default_field='id')
    {
        $target = $this->get_class_name($target_class);
        $bridge = $this->get_class_name($bridge_class);
        $this_their_field = $this->get_their_field($this, $bridge_class); //!empty($this::their_field[$bridge_class]) ? $this::their_field[$bridge_class] : (is_string($this::their_field) ? $this::their_field : $this::their_field[0]);
        
        $this->hasMany($bridge, [$bridge_class, 'our_field'=> $this::our_field, 'their_field'=>$this_their_field]);

        $this->_m2m[$target] = [$target_class, $bridge_class, $default_field];
        $this->addMethod($target, function ($m, $data='', $field='') {
            $this->get_zombie($target_class, $bridge_class, $default_field);
     
            $field = empty($field) ? $default_field : $field;

            if (!empty($data)) {
                $data = is_array($data) ? $data : explode(',', $data);
                return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class))->addCondition($field, $data);
            }
            else
            {
                return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class));
            }
        });

        $this->_m2m['add'.$target] = [$target_class, $bridge_class, $default_field];
        $this->addMethod('add' . $target, function ($m, $data, $field='') {
            $this->get_zombie($target_class, $bridge_class, $default_field);
            
            $field = empty($field) ? $default_field : $field;
            if (!$this->loaded()) {
                throw new \Exception('No data loaded for ' . $this->get_class_name($this) .'. ' . $this->get_class_name($target_class) . ' can not be accessed without a dataset loaded.');
            }

            $this_their_field = $this->get_their_field($this, $bridge_class); //!empty($this::their_field[$bridge_class]) ? $this::their_field[$bridge_class] : (is_string($this::their_field) ? $this::their_field : $this::their_field[0]);
            $target_their_field = $this->get_their_field($target_class, $bridge_class); //!empty($target_class::their_field[$bridge_class]) ? $target_class::their_field[$bridge_class] : (is_string($target_class::their_field) ? $target_class::their_field : $target_class::their_field[0]);

            if (!empty($data)) {
                $data = is_array($data) ? $data : explode(',', $data);
                try {
                    foreach ($data as $item) {
                        $t = new $target_class($this->persistence);
                        $t->loadBy($field, trim($item));
                        $b = new $bridge_class($this->persistence);
                        if (!($b->addCondition($this_their_field, $this[$this::our_field])->addCondition($target_their_field, $t[$target_class::our_field])->action('count')->getOne())) {
                            $b->unload();
                            $b[$this_their_field] = $this[$this::our_field];
                            $b[$target_their_field] = $t[$target_class::our_field];
                            $b->save();
                        }
                    }
                } catch (\atk4\data\Exception $e) {
                    echo $e;
                    return false;
                }
            }
            return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class));
        });

        $this->_m2m['remove'.$target] = [$target_class, $bridge_class, $default_field];
        $this->addMethod('remove' . $target, function ($m, $data, $field='') {
            $this->get_zombie($target_class, $bridge_class, $default_field);
            
            $field = empty($field) ? $default_field : $field;
            if (!$this->loaded()) {
                throw new \Exception('No data loaded for ' . $this->get_class_name($this) .'. ' . $this->get_class_name($target_class) . ' can not be accessed without a dataset loaded.');
            }

            $this_their_field = $this->get_their_field($this, $bridge_class); //!empty($this::their_field[$bridge_class]) ? $this::their_field[$bridge_class] : (is_string($this::their_field) ? $this::their_field : $this::their_field[0]);
            $target_their_field = $this->get_their_field($target_class, $bridge_class); //!empty($target_class::their_field[$bridge_class]) ? $target_class::their_field[$bridge_class] : (is_string($target_class::their_field) ? $target_class::their_field : $target_class::their_field[0]);

            if (!empty($data)) {
                $data = is_array($data) ? $data : explode(',', $data);
                try {
                    foreach ($data as $item) {
                        $t = new $target_class($this->persistence);
                        $t->loadBy($field, trim($item));
                        $this->ref($this->get_class_name($bridge_class))->addCondition($target_their_field, $t[$target_class::our_field])->each(function ($m) {
                            $m->delete();
                        });
                    }
                } catch (\atk4\data\Exception $e) {
                    echo $e;
                    return false;
                }
            }
            return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class));
        });

        $this->_m2m['has'.$target] = [$target_class, $bridge_class, $default_field];
        $this->addMethod('has' . $target, function ($m, $data='', $field='') {
            $this->get_zombie($target_class, $bridge_class, $default_field);
            
            $field = empty($field) ? $default_field : $field;

            if (!empty($data)) {
                $data = is_array($data) ? $data : explode(',', $data);
                return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class))->addCondition($field, $data)->action('count')->getOne();
            }
            else
            {
                return $this->ref($this->get_class_name($bridge_class))->ref($this->get_class_name($target_class))->action('count')->getOne();
            }
        });
    }

    public function addBridgeBetween($a, $b)
    {
        $a_their_field = $this->get_their_field($a, $this);
        $b_their_field = $this->get_their_field($b, $this);

        $this->addField($a_their_field);
        $this->addField($b_their_field);
        $this->hasOne($this->get_class_name($a), [$a, 'our_field'=> $a_their_field, 'their_field'=>$a::our_field]);
        $this->hasOne($this->get_class_name($b), [$b, 'our_field'=> $b_their_field, 'their_field'=>$b::our_field]);
    }

    public function get_zombie(&$target_class, &$bridge_class, &$default_field)
    {
        $trace = debug_backtrace();
        // This closure needs to know the function name it was called upon.
        // Since the function name are dynamically created based on the $target name,
        // the closure needs this info to reference the appropriate target and bridge classes.
        // $trace[4]['args'][0] contains the function name from the tryCall().
        $target_class = $this->_m2m[$trace[4]['args'][0]][0];
        $bridge_class = $this->_m2m[$trace[4]['args'][0]][1];
        $default_field = $this->_m2m[$trace[4]['args'][0]][2];
    }

    public function get_their_field($target_class, $bridge_class)
    {
        $bridge_class = is_object($bridge_class) ? get_class($bridge_class) : $bridge_class;
        return !empty($target_class::their_field[$bridge_class]) ? $target_class::their_field[$bridge_class] : (is_string($target_class::their_field) ? $target_class::their_field : $target_class::their_field[0]);
    }

    public function get_class_name($class)
    {
        return substr($class, strrpos($class, '\\') + 1);
    }
}
