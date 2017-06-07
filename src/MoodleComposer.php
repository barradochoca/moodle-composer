<?php

namespace Middag;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class MoodleComposer
{

    /**
     * preInstall
     *
     * @param \Composer\Script\Event $event
     */
    public static function preInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ PREPARANDO ------------");
        self::createInstallerDir($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * postInstall
     *
     * @param \Composer\Script\Event $event
     */
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ INSTALANDO ------------");
        self::moveMoodle($event);
        self::copyConfig($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * preUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function preUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ PREPARANDO ------------");
        self::copyConfigToRoot($event);
        self::copyVersionToRoot($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * postUpdate
     *
     * @param \Composer\Script\Event $event
     */
    public static function postUpdate(Event $event)
    {
        $io = $event->getIO();
        $io->write("------------ ATUALIZANDO ------------");
        if (self::isNewMoodle($event)) {
            self::moveMoodle($event, true);
            self::copyConfig($event);
        }
        self::cleanCache($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * preUpdatePackage
     *
     * @param \Composer\Script\Event $event
     */
    public static function preUpdatePackage(PackageEvent $event)
    {
        $io = $event->getIO();
        $io->write("------------ ATUALIZANDO ------------");
        self::setGitFileMode($event);
        $io->write("------------ CONCLUÍDO ------------");
    }

    /**
     * createInstallerDir
     *
     * @param \Composer\Script\Event $event
     */
    public static function createInstallerDir(Event $event)
    {
        $io = $event->getIO();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (!file_exists($installerdir) && !is_dir($installerdir)) {
            $io->write("Criando diretório $installerdir/");
            mkdir("$installerdir");
        } else {
            $io->write("NOTA: $installerdir/ já existe");
        }
    }

    /**
     * copyConfigToRoot
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfigToRoot(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists("$installerdir/config.php")) {
            $io->write("Copiando $installerdir/config.php para ROOT/");
            exec("cp $appDir/$installerdir/config.php $appDir");
        } else {
            $io->write("ATENÇÃO!!! $installerdir/config.php não encontrado");
        }
    }

    /**
     * copyVersionToRoot
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyVersionToRoot(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists("$installerdir/version.php")) {
            $io->write("Copiando $installerdir/version.php para ROOT/");
            exec("cp $appDir/$installerdir/version.php $appDir");
        } else {
            $io->write("ATENÇÃO!!! $installerdir/version.php não encontrado");
        }
    }

    /**
     * moveMoodle
     *
     * @param \Composer\Script\Event $event
     */
    public static function moveMoodle(Event $event, $update = false)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if ($update) {
            $io->write("Removendo $installerdir/");
            exec("rm -rf $appDir/$installerdir/");
            self::createInstallerDir($event);
        }
        $io->write("Copiando vendor/moodle/moodle para $installerdir/");
        exec("cp -r $appDir/vendor/moodle/moodle/* $appDir/$installerdir/");
    }

    /**
     * copyConfig
     *
     * @param \Composer\Script\Event $event
     */
    public static function copyConfig(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if (file_exists('config.php')) {
            $io->write("Copiando config.php para $installerdir/");
            exec("cp $appDir/config.php $appDir/$installerdir/");
        }
    }

    /**
     * setMaintenance
     *
     * @param \Composer\Script\Event $event
     * @param boolean $status
     */
    public static function setMaintenance(Event $event, $status = false)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        if ($status) {
            $io->write("Habilitando modo de manutenção");
            exec("php $appDir/$installerdir/admin/cli/maintenance.php --enable");
        } else {
            $io->write("Desabilitando modo de manutenção");
            exec("php $appDir/$installerdir/admin/cli/maintenance.php --disable");
        }
    }

    /**
     * cleanCache
     *
     * @param \Composer\Script\Event $event
     * @param boolean $status
     */
    public static function cleanCache(Event $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];
        $io->write("Limpando o cache do Moodle");
        exec("php $appDir/$installerdir/admin/cli/purge_caches.php");
    }

    /**
     * setGitFileMode
     *
     * @param \Composer\Script\Event $event
     * @param boolean $status
     */
    public static function setGitFileMode(PackageEvent $event)
    {
        $io = $event->getIO();
        $appDir =  getcwd();

        $operation = $event->getOperation();
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        }
        elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        }
        elseif ($operation instanceof UninstallOperation) {
            $package = $operation->getPackage();
        }
        if ($package && $package instanceof PackageInterface) {
            $installationManager = $event->getComposer()->getInstallationManager();
            $path = $installationManager->getInstallPath($package);
            $io->write("Atualizando pacote ", FALSE);
            $io->write($package->getName());
            if (file_exists($path)) {
                $io->write(">>> git diff | git log -1 | git config core.fileMode false | git checkout -f HEAD | git reset HEAD --hard");
                $io->write(exec("cd $path && git diff && git log -1 && git config core.fileMode false && git checkout -f HEAD && git reset HEAD --hard"));
            }
        }
    }

    /**
     * isNewMoodle
     *
     * @param \Composer\Script\Event $event
     * @param boolean $status
     */
    public static function isNewMoodle(Event $event)
    {
        define("MOODLE_INTERNAL", true);
        define("MATURITY_STABLE", 200);

        $io = $event->getIO();
        $appDir =  getcwd();
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installerdir = $extra['installerdir'];

        $oldVersion = 0;
        $newVersion = 0;

        $oldFile = $appDir."/version.php";
        if (file_exists($oldFile)) {
            require_once $oldFile;
            $oldVersion = $version;
        } else {
            return false;
        }

        $newFile = $appDir."/vendor/moodle/moodle/version.php";
        if (file_exists($newFile)) {
            require_once $newFile;
            $newVersion = $version;
        } else {
            return false;
        }

        if ($newVersion > $oldVersion) {
            $io->write("### NOVA VERSÃO DO MOODLE DETECTADA ###");
            return true;
        }

        return false;
    }

}
