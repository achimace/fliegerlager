<?php
// LoginSystem.php
require_once 'VereinsfliegerRestInterface.php';

class LoginSystem
{
    private $api;

    public function __construct()
    {
        $this->api = new VereinsfliegerRestInterface();
    }

    public function login($username, $password)
    {
        // STEP 1: Authenticate username and password with the API
        if ($this->api->SignIn($username, $password)) {
            
            $user_data = $this->api->GetUser();

            // --- ROLE VERIFICATION LOGIC ---

            // STEP 2: Define which roles are allowed to access the admin area.
            // You can easily add or remove roles from this list.
            $allowed_roles = ['Vorstand', 'Gästeverwaltung', '1.Vorstand']; 

            // STEP 3: Check if the user has at least one of the allowed roles.
            if (isset($user_data['roles']) && is_array($user_data['roles'])) {
                // array_intersect finds all roles that are in BOTH arrays.
                $matching_roles = array_intersect($allowed_roles, $user_data['roles']);

                // If the resulting array is not empty, the user has at least one required role.
                if (!empty($matching_roles)) {
                    // SUCCESS: User is authenticated AND has a correct role.
                    return ['status' => true, 'message' => 'Login successful', 'user' => $user_data];
                }
            }
            
            // FAILURE: Password was correct, but the user lacks a necessary role.
            $message = 'Anmeldung erfolgreich, aber Sie haben nicht die erforderliche Rolle, um auf diese Anwendung zuzugreifen. Erforderlich: ' . implode(' oder ', $allowed_roles);
            return ['status' => false, 'message' => $message];

        } else {
            // FAILURE: Username or password was incorrect.
            return ['status' => false, 'message' => 'Benutzername oder Passwort ist ungültig.'];
        }
    }
}
?>