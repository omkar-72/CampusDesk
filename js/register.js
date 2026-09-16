/* =========================================================
   CAMPUSDESK - REGISTER FUNCTIONS
   ========================================================= */

function validateRegister(){

    let password = document.getElementById("password").value;
    let confirm = document.getElementById("confirm_password").value;

    if(password !== confirm){
        alert("Passwords do not match.");
        return false;
    }

    if(password.length < 6){
        alert("Password must be at least 6 characters.");
        return false;
    }

    return true;
}