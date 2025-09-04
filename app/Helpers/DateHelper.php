// app/Helpers/DateHelper.php
class DateHelper {
    public static function formatarMesAno($data) { ... }
    public static function mesesRestantes($dataFim) { ... }
    public static function statusContrato($dataFim) { ... }
}

// app/Helpers/SecurityHelper.php
class SecurityHelper {
    public static function sanitize($input) { ... }
    public static function validateInput($data, $rules) { ... }
    public static function csrfToken() { ... }
}