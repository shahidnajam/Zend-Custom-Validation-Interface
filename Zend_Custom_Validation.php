<?php
/**
 * 
 **/
class Validation
{
    /**
     * Determine if the system will translate the messages, 
     * using Zend_Translate that needs to be in the register with this same
     * name
     *  
     * @var boolean
     **/
    protected $_translate = true;
    
    protected $_rules;

    protected $_messages;

    protected $_return;

    protected $_values;

    /**
     * Set the rules, array of rules: array('FieldName'=>'rule1|rule2|rule3[parameter]');
     *
     * @return void
     * @param array $rules
     * @author Rafael
     **/
    public function setRules(array $rules)
    {
        $this->_rules = $rules;
    }

    public function setTranslate($value)
    {
        $this->_translate = $value;
    }

    /**
     * Set messages
     *
     * @return void
     * @param array $messages
     * @author Rafael Nexus
     **/
    public function setMessages(array $messages)
    {
       //Try to translate if its set to 
       if ($this->_translate == true && 
           $translate = Zend_Registry::get('Zend_Translate')) {

            foreach($messages as $k=>$m)
                $messages[$k] = $translate->_($m);
        } 
   
        $this->_messages = $messages;    
    }

    /**
     * Run the rules against the values provided 
     *
     * @return void
     * @param array $values //associative array 'FieldName'=>Value
     * @author Rafael Nexus
     **/
    public function run(array $validate)
    {
        $this->_values = $validate;

        //if the rules are empty than everything is valid
        if (empty($this->_rules))
            return $values;


        $return = array();

        //fields names from the values
        $validateKeys = array_keys($validate);

        foreach ($this->_rules as $fieldName=>$rules) {

            //verify if the list of rules is an array
            if (!is_array($rules))
                continue;

            //Actually validate
            if (isset($validate[$fieldName]))
                $return[$fieldName] = $this->_validate($fieldName);
        }        

        return (in_array(false,$return))? false : true;
    }

    /**
     * Get the values 
     *
     * @return void
     * @author Rafael Nexus
     **/
    public function getResult()
    {
        return $this->_return;    
    }

    /**
     * undocumented function
     *
     * @return void
     * @author Rafael Nexus
     **/
    public function getErrorMessages()
    {
        $messages = array();

        foreach ($this->_return as $k=>$v)
            if ($v['response']==false)
               $messages[$k] = $v['message'];

        return $messages;
    }

    /**
     * Actually validate the rules
     *
     * @return void
     * @author Rafael Nexus
     **/
    protected function _validate($fieldName)
    {
        if (!isset($this->_rules[$fieldName]))
            return true;
            
        $validatorChain = new Zend_Validate();
        foreach ($this->_rules[$fieldName] as $k=>$v) {

            //determine theh paramters and the validator name
            if (is_array($v)) {
                $parameters = $v;
                $validatorName = $k;
            } else {
                $parameters = false;
                $validatorName = $v;
            }

            $validatorName = ucfirst($validatorName);

            //set the name of the validator
            $validator = "Zend_Validate_{$validatorName}"; 

            if ($parameters)
                $validatorChain->addValidator(new $validator($parameters));
            else
                $validatorChain->addValidator(new $validator());

            if (!$validatorChain->isValid($this->_values[$fieldName]))
                return $this->_registerReturn($fieldName,false);
        }

        return $this->_registerReturn($fieldName,true);
    }


    /**
     * Register the response
     *
     * @return void
     * @param string $fieldName
     * @param boolean $response
     * @author Rafael Nexus
     **/
    protected function _registerReturn($fieldName,$response)
    {
        $this->_return[$fieldName] = array('response'=>$response,
                                           'value'=>$this->_values[$fieldName],
                                           'message'=>$this->_messages[$fieldName]);

        return $response;
    }
}
