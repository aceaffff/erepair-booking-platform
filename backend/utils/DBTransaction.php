<?php
/**
 * Database Transaction Helper
 * Provides safe transaction handling methods to prevent "There is no active transaction" errors
 */
class DBTransaction {
    
    /**
     * Start a transaction only if none is currently active
     * 
     * @param PDO $pdo Database connection
     * @return bool True if transaction was started, false if one was already active
     */
    public static function start(PDO $pdo): bool {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            return true;
        }
        return false;
    }
    
    /**
     * Commit a transaction only if one is currently active
     * 
     * @param PDO $pdo Database connection
     * @return bool True if transaction was committed, false if none was active
     */
    public static function commit(PDO $pdo): bool {
        if ($pdo->inTransaction()) {
            $pdo->commit();
            return true;
        }
        return false;
    }
    
    /**
     * Rollback a transaction only if one is currently active
     * 
     * @param PDO $pdo Database connection
     * @return bool True if transaction was rolled back, false if none was active
     */
    public static function rollback(PDO $pdo): bool {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            return true;
        }
        return false;
    }
    
    /**
     * Execute a callback within a transaction
     * Automatically handles commit/rollback based on success/failure
     * 
     * @param PDO $pdo Database connection
     * @param callable $callback Function to execute within transaction
     * @return mixed Return value from callback
     * @throws Exception If callback throws an exception
     */
    public static function execute(PDO $pdo, callable $callback) {
        $started = self::start($pdo);
        
        try {
            $result = $callback($pdo);
            if ($started) {
                self::commit($pdo);
            }
            return $result;
        } catch (Exception $e) {
            if ($started) {
                self::rollback($pdo);
            }
            throw $e;
        }
    }
    
    /**
     * Clean up any active transaction
     * Useful for cleanup in error handlers
     * 
     * @param PDO $pdo Database connection
     * @return bool True if a transaction was rolled back, false if none was active
     */
    public static function cleanup(PDO $pdo): bool {
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
                return true;
            } catch (Exception $e) {
                error_log("Transaction cleanup error: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }
}
