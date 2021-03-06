<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package CoreConsole
 */

namespace Piwik\Plugins\CoreConsole;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package CoreConsole
 */
class GenerateTest extends GeneratePluginBase
{
    protected function configure()
    {
        $this->setName('generate:test')
            ->setDescription('Adds a test to an existing plugin')
            ->addOption('pluginname', null, InputOption::VALUE_REQUIRED, 'The name of an existing plugin')
            ->addOption('testname', null, InputOption::VALUE_REQUIRED, 'The name of the test you want to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pluginName = $this->getPluginName($input, $output);
        $testName   = $this->getTestName($input, $output);

        $exampleFolder  = PIWIK_INCLUDE_PATH . '/plugins/ExamplePlugin';
        $replace        = array('ExamplePlugin' => $pluginName, 'SimpleTest' => $testName);
        $whitelistFiles = array('/tests', '/tests/SimpleTest.php');

        $this->copyTemplateToPlugin($exampleFolder, $pluginName, $replace, $whitelistFiles);

        $this->writeSuccessMessage($output, array(
             sprintf('Test %s for plugin %s generated.', $testName, $pluginName),
             'You can now start writing beautiful tests',
             'Enjoy!'
        ));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \RunTimeException
     */
    private function getTestName(InputInterface $input, OutputInterface $output)
    {
        $testname = $input->getOption('testname');

        $validate = function ($testname) {
            if (empty($testname)) {
                throw new \InvalidArgumentException('You have to enter a test name');
            }

            return $testname;
        };

        if (empty($testname)) {
            $dialog = $this->getHelperSet()->get('dialog');
            $testname = $dialog->askAndValidate($output, 'Enter the name of the test: ', $validate);
        } else {
            $validate($testname);
        }

        $testname = ucfirst($testname);

        return $testname;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \RunTimeException
     */
    private function getPluginName(InputInterface $input, OutputInterface $output)
    {
        $pluginNames = $this->getPluginNames();

        $validate = function ($pluginName) use ($pluginNames) {
            if (!in_array($pluginName, $pluginNames)) {
                throw new \InvalidArgumentException('You have to enter the name of an existing plugin');
            }

            return $pluginName;
        };

        $pluginName = $input->getOption('pluginname');

        if (empty($pluginName)) {
            $dialog = $this->getHelperSet()->get('dialog');
            $pluginName = $dialog->askAndValidate($output, 'Enter the name of your plugin: ', $validate, false, null, $pluginNames);
        } else {
            $validate($pluginName);
        }

        $pluginName = ucfirst($pluginName);

        return $pluginName;
    }


}