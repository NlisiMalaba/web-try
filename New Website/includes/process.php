<?php
// Process class for handling background processes
class Process {
    private $callback;
    private $pid;
    private $running = false;
    private $status = null;
    private $isWindows;

    public function __construct($callback) {
        $this->callback = $callback;
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function start() {
        if ($this->running) {
            return false;
        }

        if ($this->isWindows) {
            // Windows alternative - use background process
            $cmd = sprintf(
                'start /B php -r "require __DIR__ . \'/includes/auth.php\'; require __DIR__ . \'/includes/db.php\'; %s"',
                $this->callback
            );
            pclose(popen($cmd, "r"));
            $this->running = true;
            return true;
        }

        // For non-Windows systems
        if (!extension_loaded('pcntl')) {
            dl('pcntl.so');
        }

        if (!extension_loaded('posix')) {
            dl('posix.so');
        }

        $pid = pcntl_fork();
        
        if ($pid == -1) {
            throw new Exception('Failed to start process');
        } elseif ($pid == 0) {
            // Child process
            try {
                ($this->callback)();
            } catch (Exception $e) {
                error_log('Process error: ' . $e->getMessage());
            }
            exit(0);
        }
        
        $this->pid = $pid;
        $this->running = true;
        return $this->pid;
    }

    public function stop() {
        if (!$this->running) {
            return false;
        }

        if ($this->isWindows) {
            // Windows alternative - no reliable way to terminate background process
            $this->running = false;
            return true;
        }

        if ($this->pid) {
            // Send SIGTERM signal
            if (function_exists('posix_kill')) {
                posix_kill($this->pid, SIGTERM);
            }
            
            // Wait for process to terminate
            pcntl_waitpid($this->pid, $this->status, WNOHANG);
        }

        $this->pid = null;
        $this->running = false;
        return true;
    }

    public function isRunning() {
        return $this->running;
    }

    public function getStatus() {
        return $this->status;
    }
}
