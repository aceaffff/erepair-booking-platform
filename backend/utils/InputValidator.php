<?php

/**
 * Comprehensive Input Validation Utility
 * Prevents SQL injection, XSS, and other security vulnerabilities
 */
class InputValidator {
    
    // Common validation patterns
    const PATTERN_EMAIL = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    const PATTERN_PHONE = '/^[\+]?[0-9\s\-\(\)]{7,20}$/';
    const PATTERN_ALPHANUMERIC = '/^[a-zA-Z0-9\s]+$/';
    const PATTERN_ALPHA = '/^[a-zA-Z\s]+$/';
    const PATTERN_NUMERIC = '/^[0-9]+$/';
    const PATTERN_DECIMAL = '/^[0-9]+(\.[0-9]+)?$/';
    const PATTERN_DATE = '/^\d{4}-\d{2}-\d{2}$/';
    const PATTERN_TIME = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
    const PATTERN_DATETIME = '/^\d{4}-\d{2}-\d{2} ([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
    const PATTERN_TOKEN = '/^[a-zA-Z0-9]{32,128}$/';
    const PATTERN_VERIFICATION_CODE = '/^[0-9]{6}$/';
    const PATTERN_PASSWORD_RESET_CODE = '/^[0-9]{6,12}$/';
    
    // SQL injection prevention patterns
    const PATTERN_SQL_INJECTION = '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)|(\b(OR|AND)\s+\d+\s*=\s*\d+)|(\b(OR|AND)\s+\'\w*\'=\'\w*\')|(\b(OR|AND)\s+\"\w*\"=\"\w*\")|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISXML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CHOOSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CASE\s+WHEN\s+[^)]*\))|(\b(OR|AND)\s+\w+\s*IF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IFNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\"\w*\"=\"\w*\")|(\b(OR|AND)\s+\'\w*\'=\'\w*\')|(\b(OR|AND)\s+\d+\s*=\s*\d+)|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISXML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CHOOSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CASE\s+WHEN\s+[^)]*\))|(\b(OR|AND)\s+\w+\s*IF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IFNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\"\w*\"=\"\w*\")|(\b(OR|AND)\s+\'\w*\'=\'\w*\')|(\b(OR|AND)\s+\d+\s*=\s*\d+)|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISXML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CHOOSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CASE\s+WHEN\s+[^)]*\))|(\b(OR|AND)\s+\w+\s*IF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IFNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NULLIF\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*COALESCE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNULL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISJSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISDATE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*ISNUMERIC\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*TRY_CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*PARSE\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONVERT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CAST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*XML\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*JSON\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOGRAPHY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*GEOMETRY\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SPATIAL\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FULLTEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*FREETEXT\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*CONTAINS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*AGAINST\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*MATCH\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*SOUNDS\s+LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*RLIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*RLIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*REGEXP\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*REGEXP\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*LIKE\s+\'[^\']*\'|\b(OR|AND)\s+\w+\s*LIKE\s+\"[^\"]*\")|(\b(OR|AND)\s+\w+\s*NOT\s+IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*NOT\s+EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*EXISTS\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*IS\s+NOT\s+NULL)|(\b(OR|AND)\s+\w+\s*IS\s+NULL)|(\b(OR|AND)\s+\w+\s*BETWEEN\s+\w+\s+AND\s+\w+)|(\b(OR|AND)\s+\w+\s*IN\s*\([^)]*\))|(\b(OR|AND)\s+\w+\s*LIKE\s+\w+)|(\b(OR|AND)\s+\w+\s*=\s*\w+)|(\b(OR|AND)\s+\"\w*\"=\"\w*\")|(\b(OR|AND)\s+\'\w*\'=\'\w*\')|(\b(OR|AND)\s+\d+\s*=\s*\d+)/i';
    
    // Allowed values for enums
    const ALLOWED_ROLES = ['customer', 'shop_owner', 'technician', 'admin'];
    const ALLOWED_STATUSES = ['pending', 'approved', 'rejected', 'active', 'inactive'];
    const ALLOWED_BOOKING_STATUSES = ['pending', 'approved', 'assigned', 'in_progress', 'completed', 'cancelled'];
    const ALLOWED_SKILL_LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];
    const ALLOWED_FILE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    
    /**
     * Validate and sanitize string input
     */
    public static function validateString($input, $minLength = 1, $maxLength = 255, $allowEmpty = false): ?string {
        if ($input === null || $input === '') {
            return $allowEmpty ? '' : null;
        }
        
        // Convert to string and trim
        $input = trim((string)$input);
        
        // Check length
        if (strlen($input) < $minLength || strlen($input) > $maxLength) {
            return null;
        }
        
        // Remove null bytes and control characters
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        return $input;
    }
    
    /**
     * Validate and sanitize email
     */
    public static function validateEmail($email): ?string {
        if (empty($email)) return null;
        
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        if (!preg_match(self::PATTERN_EMAIL, $email)) {
            return null;
        }
        
        // Additional length check
        if (strlen($email) > 254) {
            return null;
        }
        
        return strtolower($email);
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone, $allowEmpty = true): ?string {
        if (empty($phone)) {
            return $allowEmpty ? '' : null;
        }
        
        $phone = trim((string)$phone);
        
        if (!preg_match(self::PATTERN_PHONE, $phone)) {
            return null;
        }
        
        return $phone;
    }
    
    /**
     * Validate Philippine mobile number (starts with 09, exactly 11 digits)
     */
    public static function validatePhilippineMobile($phone, $allowEmpty = true): ?string {
        if (empty($phone)) {
            return $allowEmpty ? '' : null;
        }
        
        $phone = trim((string)$phone);
        
        // Remove any non-digit characters for validation
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it starts with 09 and is exactly 11 digits
        if (!preg_match('/^09[0-9]{9}$/', $cleanPhone)) {
            return null;
        }
        
        return $cleanPhone;
    }
    
    /**
     * Validate integer with range
     */
    public static function validateInteger($input, $min = null, $max = null): ?int {
        if ($input === null || $input === '') return null;
        
        if (!is_numeric($input)) return null;
        
        $value = (int)$input;
        
        if ($min !== null && $value < $min) return null;
        if ($max !== null && $value > $max) return null;
        
        return $value;
    }
    
    /**
     * Validate positive integer (ID fields)
     */
    public static function validateId($input): ?int {
        $id = self::validateInteger($input, 1);
        return $id;
    }
    
    /**
     * Validate decimal/float
     */
    public static function validateDecimal($input, $min = null, $max = null, $precision = 2): ?float {
        if ($input === null || $input === '') return null;
        
        if (!is_numeric($input)) return null;
        
        $value = round((float)$input, $precision);
        
        if ($min !== null && $value < $min) return null;
        if ($max !== null && $value > $max) return null;
        
        return $value;
    }
    
    /**
     * Validate date (YYYY-MM-DD format)
     */
    public static function validateDate($date): ?string {
        if (empty($date)) return null;
        
        $date = trim((string)$date);
        
        if (!preg_match(self::PATTERN_DATE, $date)) {
            return null;
        }
        
        // Validate actual date
        $parts = explode('-', $date);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            return null;
        }
        
        return $date;
    }
    
    /**
     * Validate time (HH:MM format)
     */
    public static function validateTime($time): ?string {
        if (empty($time)) return null;
        
        $time = trim((string)$time);
        
        if (!preg_match(self::PATTERN_TIME, $time)) {
            return null;
        }
        
        return $time;
    }
    
    /**
     * Validate datetime
     */
    public static function validateDateTime($datetime): ?string {
        if (empty($datetime)) return null;
        
        $datetime = trim((string)$datetime);
        
        if (!preg_match(self::PATTERN_DATETIME, $datetime)) {
            return null;
        }
        
        // Validate using DateTime
        try {
            $dt = new DateTime($datetime);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Validate token (session, verification, etc.)
     */
    public static function validateToken($token): ?string {
        if (empty($token)) return null;
        
        $token = trim((string)$token);
        
        if (!preg_match(self::PATTERN_TOKEN, $token)) {
            return null;
        }
        
        return $token;
    }
    
    /**
     * Validate verification code
     */
    public static function validateVerificationCode($code): ?string {
        if (empty($code)) return null;
        
        $code = preg_replace('/[^0-9]/', '', (string)$code);
        
        if (!preg_match(self::PATTERN_VERIFICATION_CODE, $code)) {
            return null;
        }
        
        return $code;
    }
    
    /**
     * Validate password reset code
     */
    public static function validatePasswordResetCode($code): ?string {
        if (empty($code)) return null;
        
        $code = preg_replace('/[^0-9]/', '', (string)$code);
        
        if (!preg_match(self::PATTERN_PASSWORD_RESET_CODE, $code)) {
            return null;
        }
        
        return $code;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password, $minLength = 6): ?string {
        if (empty($password)) return null;
        
        $password = (string)$password;
        
        if (strlen($password) < $minLength) {
            return null;
        }
        
        // Check for null bytes
        if (strpos($password, "\0") !== false) {
            return null;
        }
        
        return $password;
    }
    
    /**
     * Validate enum values
     */
    public static function validateEnum($value, array $allowedValues): ?string {
        if (empty($value)) return null;
        
        $value = trim(strtolower((string)$value));
        
        if (!in_array($value, $allowedValues, true)) {
            return null;
        }
        
        return $value;
    }
    
    /**
     * Validate role
     */
    public static function validateRole($role): ?string {
        return self::validateEnum($role, self::ALLOWED_ROLES);
    }
    
    /**
     * Validate status
     */
    public static function validateStatus($status): ?string {
        return self::validateEnum($status, self::ALLOWED_STATUSES);
    }
    
    /**
     * Validate booking status
     */
    public static function validateBookingStatus($status): ?string {
        return self::validateEnum($status, self::ALLOWED_BOOKING_STATUSES);
    }
    
    /**
     * Validate skill level
     */
    public static function validateSkillLevel($level): ?string {
        return self::validateEnum($level, self::ALLOWED_SKILL_LEVELS);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $maxSize = 5242880, $allowedTypes = null): ?array {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Check file size (default 5MB)
        if ($file['size'] > $maxSize) {
            return null;
        }
        
        // Validate MIME type
        $allowedTypes = $allowedTypes ?: self::ALLOWED_FILE_TYPES;
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes, true)) {
            return null;
        }
        
        // Additional validation for images
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return null;
            }
        }
        
        return [
            'name' => basename($file['name']),
            'type' => $mimeType,
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ];
    }
    
    /**
     * Sanitize HTML content (for descriptions, notes, etc.)
     */
    public static function sanitizeHtml($input, $allowedTags = '<p><br><strong><em><u>'): ?string {
        if (empty($input)) return null;
        
        $input = trim((string)$input);
        
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Strip tags except allowed ones
        $input = strip_tags($input, $allowedTags);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Validate JSON input
     */
    public static function validateJsonInput($jsonString): ?array {
        if (empty($jsonString)) return null;
        
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Validate array of IDs
     */
    public static function validateIdArray($input): ?array {
        if (!is_array($input)) return null;
        
        $ids = [];
        foreach ($input as $id) {
            $validId = self::validateId($id);
            if ($validId !== null) {
                $ids[] = $validId;
            }
        }
        
        return empty($ids) ? null : array_unique($ids);
    }
    
    /**
     * Validate coordinates (latitude/longitude)
     */
    public static function validateLatitude($lat): ?float {
        $lat = self::validateDecimal($lat, -90, 90, 6);
        return $lat;
    }
    
    public static function validateLongitude($lng): ?float {
        $lng = self::validateDecimal($lng, -180, 180, 6);
        return $lng;
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName, $prefix = ''): string {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return $prefix . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Validate and sanitize multiple inputs at once
     */
    public static function validateBatch(array $inputs, array $rules): array {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $inputs[$field] ?? null;
            $method = $rule['method'] ?? 'validateString';
            $params = $rule['params'] ?? [];
            $required = $rule['required'] ?? false;
            
            if ($value === null || $value === '') {
                if ($required) {
                    $errors[] = "Field '$field' is required";
                    continue;
                }
                $validated[$field] = null;
                continue;
            }
            
            $result = call_user_func_array([self::class, $method], array_merge([$value], $params));
            
            if ($result === null) {
                $errors[] = "Field '$field' is invalid";
            } else {
                $validated[$field] = $result;
            }
        }
        
        return ['data' => $validated, 'errors' => $errors];
    }
    
    /**
     * Detect and prevent SQL injection attempts
     */
    public static function detectSqlInjection($input): bool {
        if (empty($input)) return false;
        
        $input = strtolower(trim((string)$input));
        
        // Check against SQL injection patterns
        if (preg_match(self::PATTERN_SQL_INJECTION, $input)) {
            return true;
        }
        
        // Additional checks for common SQL injection techniques
        $dangerousPatterns = [
            // Basic SQL commands
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script)\b/i',
            // Comment patterns
            '/--|\/\*|\*\/|#/',
            // Quote manipulation
            '/[\'"]\s*(or|and)\s*[\'"]?\d*[\'"]?\s*=\s*[\'"]?\d*[\'"]?/i',
            // Boolean-based blind SQL injection
            '/\b(or|and)\s+\d+\s*=\s*\d+/i',
            // Time-based blind SQL injection
            '/\b(sleep|waitfor|benchmark)\s*\(/i',
            // Error-based SQL injection
            '/\b(extractvalue|updatexml|exp|floor|rand)\s*\(/i',
            // Stacked queries
            '/;\s*(select|insert|update|delete|drop|create|alter)/i',
            // Union-based SQL injection
            '/union\s+(all\s+)?select/i',
            // Information schema queries
            '/information_schema|mysql\.user|pg_user|sys\.databases/i',
            // System functions
            '/\b(user|database|version|@@version|@@hostname|@@datadir)\b/i',
            // File operations
            '/\b(load_file|into\s+outfile|into\s+dumpfile)\b/i',
            // Stored procedures
            '/\b(sp_|xp_|ms_)\w+/i',
            // Conditional statements
            '/\b(if|case|when|then|else|end)\b/i',
            // String functions
            '/\b(concat|substring|ascii|char|hex|unhex)\s*\(/i',
            // Mathematical functions
            '/\b(pow|sqrt|abs|ceil|floor|round)\s*\(/i',
            // Date functions
            '/\b(now|curdate|curtime|date|time|year|month|day)\s*\(/i',
            // Aggregation functions
            '/\b(count|sum|avg|min|max|group_concat)\s*\(/i',
            // Window functions
            '/\b(row_number|rank|dense_rank|ntile|lead|lag)\s*\(/i',
            // Regular expressions
            '/\b(regexp|rlike|soundex)\b/i',
            // Bitwise operations
            '/\b(bit_and|bit_or|bit_xor|bit_not)\s*\(/i',
            // Encryption functions
            '/\b(md5|sha1|sha2|aes_encrypt|aes_decrypt)\s*\(/i',
            // Compression functions
            '/\b(compress|uncompress|zip|unzip)\s*\(/i',
            // JSON functions
            '/\b(json_extract|json_object|json_array)\s*\(/i',
            // Spatial functions
            '/\b(point|linestring|polygon|geometry|geography)\s*\(/i',
            // XML functions
            '/\b(xml|extractvalue|updatexml)\s*\(/i',
            // Miscellaneous dangerous patterns
            '/\b(declare|set|begin|end|goto|return)\b/i',
            '/\b(open|close|cursor|fetch|deallocate)\b/i',
            '/\b(transaction|commit|rollback|savepoint)\b/i',
            '/\b(grant|revoke|deny|backup|restore)\b/i',
            '/\b(kill|shutdown|restart|reload)\b/i',
            '/\b(show|describe|explain|analyze)\b/i',
            '/\b(optimize|repair|check|flush)\b/i',
            '/\b(lock|unlock|get_lock|release_lock)\b/i',
            '/\b(load|source|use|connect)\b/i',
            '/\b(change|rename|truncate|vacuum)\b/i',
            '/\b(attach|detach|pragma|vacuum)\b/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Enhanced string validation with SQL injection prevention
     */
    public static function validateSecureString($input, $minLength = 1, $maxLength = 255, $allowEmpty = false): ?string {
        if ($input === null || $input === '') {
            return $allowEmpty ? '' : null;
        }
        
        // Convert to string and trim
        $input = trim((string)$input);
        
        // Check for SQL injection attempts
        if (self::detectSqlInjection($input)) {
            error_log("SQL Injection attempt detected: " . substr($input, 0, 100));
            return null;
        }
        
        // Check length
        if (strlen($input) < $minLength || strlen($input) > $maxLength) {
            return null;
        }
        
        // Remove null bytes and control characters
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Additional sanitization
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Enhanced email validation with SQL injection prevention
     */
    public static function validateSecureEmail($email): ?string {
        if (empty($email)) return null;
        
        // Check for SQL injection attempts first
        if (self::detectSqlInjection($email)) {
            error_log("SQL Injection attempt in email: " . substr($email, 0, 100));
            return null;
        }
        
        // Block dangerous special characters that could be used for SQL injection
        $dangerousChars = ['\'', '"', ';', '--', '/*', '*/', '#', '\\', '`', '(', ')', '[', ']', '{', '}', '|', '&', '^', '%', '*', '+', '=', '<', '>', '?', '!', '~', ':', '\\', '/'];
        foreach ($dangerousChars as $char) {
            if (strpos($email, $char) !== false) {
                error_log("Dangerous character detected in email: $char");
                return null;
            }
        }
        
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        if (!preg_match(self::PATTERN_EMAIL, $email)) {
            return null;
        }
        
        // Additional length check
        if (strlen($email) > 254) {
            return null;
        }
        
        return strtolower($email);
    }
    
    /**
     * Enhanced password validation with SQL injection prevention
     */
    public static function validateSecurePassword($password, $minLength = 6): ?string {
        if (empty($password)) return null;
        
        // Check for SQL injection attempts
        if (self::detectSqlInjection($password)) {
            error_log("SQL Injection attempt in password");
            return null;
        }
        
        // Block dangerous special characters that could be used for SQL injection
        $dangerousChars = ['\'', '"', ';', '--', '/*', '*/', '#', '\\', '`', '(', ')', '[', ']', '{', '}', '|', '&', '^', '%', '*', '+', '=', '<', '>', '?', '!', '~', ':', '\\', '/'];
        foreach ($dangerousChars as $char) {
            if (strpos($password, $char) !== false) {
                error_log("Dangerous character detected in password: $char");
                return null;
            }
        }
        
        $password = (string)$password;
        
        if (strlen($password) < $minLength) {
            return null;
        }
        
        // Check for null bytes
        if (strpos($password, "\0") !== false) {
            return null;
        }
        
        // Additional security checks
        if (strlen($password) > 128) {
            return null;
        }
        
        // Only allow alphanumeric characters, dots, hyphens, and underscores for passwords
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $password)) {
            error_log("Invalid characters detected in password");
            return null;
        }
        
        return $password;
    }
}
