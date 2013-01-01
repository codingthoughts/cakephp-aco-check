<?php
App::uses('AppShell', 'Console/Command');
App::uses('Controller', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('AclComponent', 'Controller/Component');

/**
 * Aco checker shell extension for CakePHP
 * This extension will scan the entire app and display what ACO's are missing in ACL
 *
 * @author Abhishek Gupta <abhi.gupta200297@gmail.com>
 * @version v1
 */
class AcoCheckShell extends AppShell
{
    private $_acl;
    /*The $_methodsBlacklist array is a list of controller actions you want to exclude from the
        scan. It should be in the form
        array(
        'controller' => array('methodName1','methodName2'),
        'controller1' => array('methodName1','methodName2')
        )
    */
    private $_methodsBlacklist = array();
    private $_listOfControllers = array();
    // Any controllers you want to put in Blacklist should be added here.
    private $_controllersBlacklist = array('App');
    private $_suggestedCommands = array();
    private $_baseMethodNames = array();


    /**
    Shell startup method
     */
    public function startup()
    {
        $collection = new ComponentCollection();
        $this->_acl = new AclComponent($collection);
        $controller = new Controller();
        $this->_acl->startup($controller);
        $this->_listOfControllers = $this->_getControllerNamesWithBlacklistRemoved();
        $this->_baseMethodNames = $this->_getMethodNamesInClass('App');
        $this->_printWelcomeMessage();
    }


    /**
     * Main logic of the application
     */
    public function main()
    {
        $aco = $this->_acl->Aco;
        $rootControllerName = $this->_getUserInput("Enter root controller Name", 'controllers');
        $root = $aco->node($rootControllerName);

        if (!$root) {
            echo "The root aco <$rootControllerName> does not exist. Please create it first.\n";
            $this->_addToSuggestedCommands("cake acl create aco root $rootControllerName");
            $this->_printSuggestedCommands();
            exit();
        }

        foreach ($this->_listOfControllers as $controllerName) {
            $methodList = $this->_getCleanedUpMethodsFromController($controllerName);
            $controllerAcoFound = $aco->node('controllers/' . $controllerName);

            if (!$controllerAcoFound) {
                $this->out(__d('cake_console', "Could not find ACO for <<error>" . $controllerName . "</error>> controller and all its methods in the ACL."));
                $this->_addToSuggestedCommands("cake acl create aco " . $rootControllerName . " " . $controllerName);
                foreach ($methodList as $methodName) {
                    $this->_addToSuggestedCommands("cake acl create aco " . $rootControllerName . "/" . $controllerName . " " . $methodName);
                }
            } else {
                $this->out(__d('cake_console', "Found ACO for <<success>" . $controllerName . "</success>> controller in the ACL."));
                foreach ($methodList as $methodName) {
                    $methodAcoFound = $aco->node('controllers/' . $controllerName . '/' . $methodName);
                    if (!$methodAcoFound) {
                        $this->out(__d('cake_console', "\tACO for <<error>" . $methodName . "</error>> not found."));
                        $this->_addToSuggestedCommands("cake acl create aco " . $rootControllerName . "/" . $controllerName . " " . $methodName);
                    }
                }

            }
        }

        $this->_exitIfAcosAreUpToDate();
        $wantToSeeSuggestions = $this->_getUserInput("Do you want to see command suggestions. Anything else to cancel", "yes");
        if ($wantToSeeSuggestions == strtolower("yes")) {
            $this->_printSuggestedCommands();
            $wantToRunSuggestions = $this->_getUserInput("Do you want to run these suggestions automatically?. Anything else to cancel", "no");
            if ($wantToRunSuggestions == strtolower("yes")) {
                $this->_executeSuggestedCommands();
            }
        }

    }

    /**
     *  Check if suggested commands array is empty.
     *  If it is, the acos are up to date. We exit here.
     */
    private function _exitIfAcosAreUpToDate()
    {
        if (empty($this->_suggestedCommands)) {
            echo "\n";
            $this->out(__d('cake_console', '<success>Your aco\'s seem up to date.</success>'));
            exit();
        }
    }

    /**
     *  We prepend proper path to cake app dir here and run all commands in the array.
     */
    private function _executeSuggestedCommands()
    {
        foreach ($this->_suggestedCommands as $command) {
            $commandToRun = APP . 'Console/' . $command;
            echo "Executing: " . $commandToRun;
            exec($commandToRun);
        }
        echo "\n\n";
        $this->out(__d('cake_console', '<success>All commands executed...</success>'));
    }

    /**
     * Gets all methods from a controller class.
     * After that, removes any private methods starting with underscore,
     * removes all methods which were inherited from the base controller class AppController
     * Finally, removes any methods which were manually set by us in the blacklist array
     * Returns the remaining method names after removing all these methods from the array
     *
     * @param $controllerName
     * @return array
     */
    private function _getCleanedUpMethodsFromController($controllerName)
    {
        $methods = $this->_getMethodNamesInClass($controllerName);

        foreach ($methods as $index => $method) {

            if (strpos($method, '_', 0) === 0) {
                unset($methods[$index]);
                continue;
            }

            if (in_array($method, $this->_baseMethodNames)) {
                unset($methods[$index]);
                continue;
            }
            // Match methods from Blacklist and clean them up from methods array
            if (array_key_exists($controllerName, $this->_methodsBlacklist)) {
                if (in_array($method, $this->_methodsBlacklist[$controllerName])) {
                    unset($methods[$index]);
                    continue;
                }
            }
        }
        return $methods;
    }

    /**
     * Gets user input from console
     *
     * @param $message
     * @param null $defaultValue
     * @return null|string
     */
    private function _getUserInput($message, $defaultValue = null)
    {
        if ($defaultValue != null) {
            fwrite(STDOUT, "$message " . "[" . $defaultValue . "]: ");
        } else {
            fwrite(STDOUT, "$message: ");
        }
        $varIn = trim(fgets(STDIN));
        if (!$varIn && $defaultValue != null) {
            return $defaultValue;
        }
        return $varIn;
    }

    /**
     * Takes in a command as an an input and adds it to the suggested commands array.
     * This array will be used at later point to display results & execute the commands
     * @param $command
     */
    private function _addToSuggestedCommands($command)
    {
        $this->_suggestedCommands[] = $command;
    }

    /**
     * Prints the suggested commands from _suggestedCommands array
     */
    private function _printSuggestedCommands()
    {
        echo "\n";
        if (APP != (exec('pwd') . '/')) {
            $this->out(__d('cake_console', '<info>Please go to your app directory. Suggested commands are: </info>'));
        } else {
            $this->out(__d('cake_console', '<info>Suggested commands are: </info>'));
        }
        $this->hr();
        echo "\n";
        foreach ($this->_suggestedCommands as $command) {
            echo "Console/" . $command . "\n";
        }
        echo "\n";
    }

    /**
     * This method gets a list og all the controllers in the app.
     * After getting a list, it removes all blacklisted controllers set by us in the beginning
     * @return array
     */
    private function _getControllerNamesWithBlacklistRemoved()
    {
        $controllerList = App::objects('Controller');
        foreach ($controllerList as $index => $controllerNameToClean) {
            $controllerList[$index] = preg_replace('/Controller$/', '', $controllerNameToClean);
        }
        $controllerList = array_diff($controllerList, $this->_controllersBlacklist);
        return $controllerList;
    }


    /**
     * This method gets the name of all class methods from a class.
     * @param null $controllerName
     * @return array
     */
    function _getMethodNamesInClass($controllerName = null)
    {
        App::import('Controller', $controllerName);

        $controllerClass = $controllerName . 'Controller';
        $methods = get_class_methods($controllerClass);
        return $methods;
    }

    /**
     *  Print welcome message
     */
    private function _printWelcomeMessage()
    {
        $this->hr();
        $this->out(__d('cake_console', '<info>CakePHP Aco checker extension</info>'));
        $this->out(__d('cake_console', '<info>Developed By: Abhishek Gupta (1 Jan 2013)</info>'));
        $this->out(__d('cake_console', '<info>Email: abhi.gupta200297@gmail.com</info>'));
        $this->out(__d('cake_console', '<info>http://www.codingthoughts.com</info>'));
        $this->hr();
        echo "\n";
    }
}
