<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-23
 * Time: 9:46
 */

namespace Inhere\Console\BuiltIn;

use Exception;
use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Strategy\ShaStrategy;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\VersionParser;
use Inhere\Console\Command;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use function strlen;

/**
 * Class SelfUpdateCommand
 *
 * @package Inhere\Console\BuiltIn
 */
class SelfUpdateCommand extends Command
{
    public const VERSION_URL = 'https://inhere.github.io/humbug/downloads/humbug.version';

    public const PHAR_URL = 'https://inhere.github.io/humbug/downloads/humbug.phar';

    public const PACKAGE_NAME = 'inhere/console';

    public const FILE_NAME = 'console.phar';

    protected static $name = 'self-update';

    protected static $description = 'Update phar package to most recent stable, pre-release or development build.';

    /**
     * @var string
     */
    protected $version;

    /**
     * Execute the command.
     *
     * @param Input  $input
     * @param Output $output
     */
    protected function execute($input, $output)
    {
        $this->version = $this->getApp()->getVersion();
        $parser        = new VersionParser;

        /**
         * Check for ancilliary options
         */
        if ($input->getOption('rollback')) {
            $this->rollback();

            return;
        }

        if ($input->getOption('check')) {
            $this->printAvailableUpdates();

            return;
        }

        /**
         * Update to any specified stability option
         */
        if ($input->getOption('dev')) {
            $this->updateToDevelopmentBuild();

            return;
        }

        if ($input->getOption('pre')) {
            $this->updateToPreReleaseBuild();

            return;
        }

        if ($input->getOption('stable')) {
            $this->updateToStableBuild();

            return;
        }

        if ($input->getOption('non-dev')) {
            $this->updateToMostRecentNonDevRemote();

            return;
        }

        /**
         * If current build is stable, only update to more recent stable
         * versions if available. User may specify otherwise using options.
         */
        if ($parser->isStable($this->version)) {
            $this->updateToStableBuild();

            return;
        }

        /**
         * By default, update to most recent remote version regardless
         * of stability.
         */
        $this->updateToMostRecentNonDevRemote();
    }

    protected function getStableUpdater()
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getPreReleaseUpdater()
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::UNSTABLE);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getMostRecentNonDevUpdater()
    {
        $updater = new Updater;
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setStability(GithubStrategy::ANY);

        return $this->getGithubReleasesUpdater($updater);
    }

    protected function getGithubReleasesUpdater(Updater $updater): Updater
    {
        $updater->getStrategy()->setPackageName(self::PACKAGE_NAME);
        $updater->getStrategy()->setPharName(self::FILE_NAME);
        $updater->getStrategy()->setCurrentLocalVersion($this->version);

        return $updater;
    }

    protected function getDevelopmentUpdater(): Updater
    {
        $updater = new Updater;
        $updater->getStrategy()->setPharUrl(self::PHAR_URL);
        $updater->getStrategy()->setVersionUrl(self::VERSION_URL);

        return $updater;
    }

    protected function updateToStableBuild(): void
    {
        $this->update($this->getStableUpdater());
    }

    protected function updateToPreReleaseBuild(): void
    {
        $this->update($this->getPreReleaseUpdater());
    }

    protected function updateToMostRecentNonDevRemote(): void
    {
        $this->update($this->getMostRecentNonDevUpdater());
    }

    protected function updateToDevelopmentBuild(): void
    {
        $this->update($this->getDevelopmentUpdater());
    }

    protected function update(Updater $updater): void
    {
        $this->output->writeln('Updating...' . PHP_EOL);
        try {
            $result = $updater->update();

            $newVersion = $updater->getNewVersion();
            $oldVersion = $updater->getOldVersion();

            if (strlen($newVersion) === 40) {
                $newVersion = 'dev-' . $newVersion;
            }

            if (strlen($oldVersion) === 40) {
                $oldVersion = 'dev-' . $oldVersion;
            }

            if ($result) {
                $this->output->writeln('<fg=green>Humbug has been updated.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $newVersion
                ));
                $this->output->writeln(sprintf(
                    '<fg=green>Previous version was:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            } else {
                $this->output->writeln('<fg=green>Humbug is currently up to date.</fg=green>');
                $this->output->writeln(sprintf(
                    '<fg=green>Current version is:</fg=green> <options=bold>%s</options=bold>.',
                    $oldVersion
                ));
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
        $this->output->write(PHP_EOL);
        $this->output->writeln('You can also select update stability using --dev, --pre (alpha/beta/rc) or --stable.');
    }

    protected function rollback(): void
    {
        $updater = new Updater;
        try {
            $result = $updater->rollback();
            if ($result) {
                $this->output->writeln('<fg=green>Humbug has been rolled back to prior version.</fg=green>');
            } else {
                $this->output->writeln('<fg=red>Rollback failed for reasons unknown.</fg=red>');
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }

    protected function printAvailableUpdates(): void
    {
        $this->printCurrentLocalVersion();
        $this->printCurrentStableVersion();
        $this->printCurrentPreReleaseVersion();
        $this->printCurrentDevVersion();
        $this->output->writeln('You can select update stability using --dev, --pre or --stable when self-updating.');
    }

    protected function printCurrentLocalVersion(): void
    {
        $this->output->writeln(sprintf(
            'Your current local build version is: <options=bold>%s</options=bold>',
            $this->version
        ));
    }

    protected function printCurrentStableVersion(): void
    {
        $this->printVersion($this->getStableUpdater());
    }

    protected function printCurrentPreReleaseVersion(): void
    {
        $this->printVersion($this->getPreReleaseUpdater());
    }

    protected function printCurrentDevVersion(): void
    {
        $this->printVersion($this->getDevelopmentUpdater());
    }

    protected function printVersion(Updater $updater): void
    {
        $stability = 'stable';
        if ($updater->getStrategy() instanceof ShaStrategy) {
            $stability = 'development';
        } elseif ($updater->getStrategy() instanceof GithubStrategy && $updater->getStrategy()
                                                                               ->getStability() === GithubStrategy::UNSTABLE
        ) {
            $stability = 'pre-release';
        }

        try {
            if ($updater->hasUpdate()) {
                $this->output->writeln(sprintf(
                    'The current %s build available remotely is: <options=bold>%s</options=bold>',
                    $stability,
                    $updater->getNewVersion()
                ));
            } elseif (false === $updater->getNewVersion()) {
                $this->output->writeln(sprintf('There are no %s builds available.', $stability));
            } else {
                $this->output->writeln(sprintf('You have the current %s build installed.', $stability));
            }
        } catch (Exception $e) {
            $this->output->writeln(sprintf('Error: <fg=yellow>%s</fg=yellow>', $e->getMessage()));
        }
    }

    protected function configure(): void
    {
        $this->createDefinition()
            // ->setName('self-update')
            // ->setDescription(self::$description)
             ->addOption(
                 'dev',
                 'd',
                 Input::OPT_BOOLEAN,
                 'Update to most recent <cyan>development build</cyan> of package.'
             )
             ->addOption(
                 'non-dev',
                 'N',
                 Input::OPT_BOOLEAN,
                 'Update to most recent non-development (alpha/beta/stable) build of package tagged on Github.'
             )
             ->addOption(
                 'pre',
                 'p',
                 Input::OPT_BOOLEAN,
                 'Update to most recent pre-release version of package (alpha/beta/rc) tagged on Github.'
             )
             ->addOption('stable', 's', Input::OPT_BOOLEAN, 'Update to most recent stable version tagged on Github.')
             ->addOption(
                 'rollback',
                 'r',
                 Input::OPT_BOOLEAN,
                 'Rollback to previous version of package if available on filesystem.'
             )
             ->addOption(
                 'check',
                 'c',
                 Input::OPT_BOOLEAN,
                 'Checks what updates are available across all possible stability tracks.'
             );
    }
}
