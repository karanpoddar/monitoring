<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\Sensor\SensorGitDirtyTree.
 */

namespace Drupal\monitoring\Plugin\monitoring\Sensor;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\SensorConfigurable;
use Drupal\monitoring\Sensor\SensorExtendedInfoInterface;

/**
 * Monitors the git repository for dirty files.
 *
 * @Sensor(
 *   id = "monitoring_git_dirty_tree",
 *   label = @Translation("Git Dirty Tree"),
 *   description = @Translation("Monitors the git repository for dirty files."),
 *   addable = FALSE
 * )
 *
 * Tracks both changed and untracked files.
 * Also supports git submodules.
 *
 * Limitations:
 * - Does not work as long as submodules are not initialized.
 * - Does not check branch / tag.
 */
class SensorGitDirtyTree extends SensorConfigurable implements SensorExtendedInfoInterface {

  /**
   * The executed command output.
   *
   * @var array
   */
  protected $cmdOutput;

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $this->cmdOutput = trim(shell_exec($this->buildCMD()));
    $result->setExpectedValue(0);

    if (!empty($this->cmdOutput)) {
      $result->setValue(count(explode("\n", $this->cmdOutput)));
      $result->addStatusMessage('Files in unexpected state');
      $result->setStatus(SensorResultInterface::STATUS_CRITICAL);
    }
    else {
      $result->setValue(0);
      $result->addStatusMessage('Git repository clean');
      $result->setStatus(SensorResultInterface::STATUS_OK);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = 'CMD: ' . $this->buildCMD();;
    $output .= "\n\n" . $this->cmdOutput;
    return $output;
  }

  /**
   * Build the command to be passed into shell_exec().
   *
   * @return string
   *   Shell command.
   */
  protected function buildCMD() {
    $repo_path = DRUPAL_ROOT . '/' . $this->info->getSetting('repo_path');
    $cmd = $this->info->getSetting('cmd');
    return 'cd ' . escapeshellarg($repo_path) . "\n$cmd  2>&1";
  }
}
