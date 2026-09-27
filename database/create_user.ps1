param(
    [ValidateSet('admin', 'operator', 'viewer')]
    [string]$Role = 'admin'
)

$ErrorActionPreference = 'Stop'
$username = Read-Host 'Username baru'
$fullName = Read-Host 'Nama lengkap'
$securePassword = Read-Host 'Password (minimal 12 karakter)' -AsSecureString
$secureConfirmation = Read-Host 'Ulangi password' -AsSecureString
$passwordPointer = [IntPtr]::Zero
$confirmationPointer = [IntPtr]::Zero

try {
    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $confirmationPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureConfirmation)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    $confirmation = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmationPointer)

    if ($password -cne $confirmation) {
        throw 'Password tidak sama.'
    }

    $env:RUANGKAMPUS_NEW_USERNAME = $username
    $env:RUANGKAMPUS_NEW_NAME = $fullName
    $env:RUANGKAMPUS_NEW_PASSWORD = $password
    $env:RUANGKAMPUS_NEW_ROLE = $Role

    $phpCommand = Get-Command 'php.exe' -ErrorAction SilentlyContinue
    if ($phpCommand) {
        $phpExecutable = $phpCommand.Source
    }
    elseif (Test-Path 'C:\xampp\php\php.exe') {
        $phpExecutable = 'C:\xampp\php\php.exe'
    }
    else {
        throw 'php.exe tidak ditemukan di PATH atau C:\xampp\php.'
    }

    & $phpExecutable (Join-Path $PSScriptRoot 'create_user.php')
    if ($LASTEXITCODE -ne 0) {
        throw 'Pembuatan akun gagal.'
    }
}
finally {
    $env:RUANGKAMPUS_NEW_PASSWORD = $null
    $env:RUANGKAMPUS_NEW_USERNAME = $null
    $env:RUANGKAMPUS_NEW_NAME = $null
    $env:RUANGKAMPUS_NEW_ROLE = $null
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
    if ($confirmationPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPointer)
    }
}