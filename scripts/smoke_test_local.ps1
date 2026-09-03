# Smoke test manual y temporal para verificar rutas locales durante el
# audit de "API y Controladores (Lógica)". No forma parte del deploy.
$base = 'http://127.0.0.1:8080'

function Get-Csrf($session) {
    $cookie = $session.Cookies.GetCookies([Uri]$base) | Where-Object { $_.Name -eq 'csrf_cookie_name' }
    return $cookie.Value
}

function Show($label, $resp) {
    Write-Output "== $label =="
    Write-Output "STATUS: $($resp.StatusCode)"
    Write-Output $resp.Content
    Write-Output ""
}

# 1. Sin sesion -> debe redirigir a login (302), no Whoops
try {
    Invoke-WebRequest -Uri "$base/portal/tramites/solicitudes/NOEXISTE" -MaximumRedirection 0 -UseBasicParsing | Out-Null
} catch {
    Write-Output "== Sin sesion (esperado 302 redirect a login) =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    Write-Output ""
}

# 2. Login como ciudadano de prueba
Invoke-WebRequest -Uri "$base/auth/login" -SessionVariable sess -UseBasicParsing | Out-Null
$csrf = Get-Csrf $sess
$body = @{ csrf_test_name = $csrf; username = 'ciudadano@example.com'; password = '12345678' }
try {
    Invoke-WebRequest -Uri "$base/auth/attempt-login" -Method POST -Body $body -WebSession $sess -MaximumRedirection 0 -UseBasicParsing | Out-Null
} catch {
    Write-Output "== Login ciudadano =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    Write-Output ""
}

# 3. Consultar folio inexistente (con sesion) -> 404 controlado
try {
    $r = Invoke-WebRequest -Uri "$base/portal/tramites/solicitudes/FOLIO-NO-EXISTE" -WebSession $sess -UseBasicParsing
    Show "Folio inexistente (esperado 404 JSON)" $r
} catch {
    Write-Output "== Folio inexistente (esperado 404 JSON) =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    $stream = $_.Exception.Response.GetResponseStream(); $reader = New-Object System.IO.StreamReader($stream)
    Write-Output $reader.ReadToEnd()
    Write-Output ""
}

# 4. Crear una solicitud UR-01 sin convocatoria (debe fallar validacion, 422)
$csrf = Get-Csrf $sess
try {
    $r = Invoke-WebRequest -Uri "$base/portal/tramites/solicitudes" -Method POST -WebSession $sess -UseBasicParsing -ContentType 'application/json' -Headers @{ 'X-CSRF-TOKEN' = $csrf } -Body '{"tramite":"UR-TT-T-01"}'
    Show "Crear UR-01 sin convocatoria (esperado 422)" $r
} catch {
    Write-Output "== Crear UR-01 sin convocatoria (esperado 422) =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    $stream = $_.Exception.Response.GetResponseStream(); $reader = New-Object System.IO.StreamReader($stream)
    Write-Output $reader.ReadToEnd()
    Write-Output ""
}

# 5. cambiar-estatus como ciudadano (sin rol admin/operador) -> debe redirigir, no Whoops
$csrf = Get-Csrf $sess
try {
    Invoke-WebRequest -Uri "$base/admin/solicitudes/cambiar-estatus/1" -Method POST -WebSession $sess -MaximumRedirection 0 -UseBasicParsing -Body @{nuevo_estatus='Pagado'; csrf_test_name=$csrf} | Out-Null
} catch {
    Write-Output "== cambiar-estatus como ciudadano sin rol (esperado 302 redirect) =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    Write-Output ""
}

# 6. Login como admin y probar cambiar-estatus con id inexistente
Invoke-WebRequest -Uri "$base/auth/login" -SessionVariable sessAdmin -UseBasicParsing | Out-Null
$csrfAdmin = Get-Csrf $sessAdmin
$body2 = @{ csrf_test_name = $csrfAdmin; username = 'admin@uriangato.gob.mx'; password = '12345678' }
try {
    Invoke-WebRequest -Uri "$base/auth/attempt-login" -Method POST -Body $body2 -WebSession $sessAdmin -MaximumRedirection 0 -UseBasicParsing | Out-Null
} catch {
    Write-Output "== Login admin =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    Write-Output ""
}

$csrfAdmin = Get-Csrf $sessAdmin
try {
    $r = Invoke-WebRequest -Uri "$base/admin/solicitudes/cambiar-estatus/999999" -Method POST -WebSession $sessAdmin -MaximumRedirection 0 -UseBasicParsing -Body @{nuevo_estatus='Pagado'; csrf_test_name=$csrfAdmin}
    Show "cambiar-estatus id inexistente como admin" $r
} catch {
    Write-Output "== cambiar-estatus id inexistente como admin =="
    Write-Output "STATUS: $($_.Exception.Response.StatusCode.value__)"
    Write-Output ""
}

# 7. Regresion: flujo existente de tramites - PortalController::tramites (vista principal, debe seguir cargando)
$r = Invoke-WebRequest -Uri "$base/portal/tramites" -WebSession $sess -UseBasicParsing
Write-Output "== Regresion /portal/tramites (esperado 200) =="
Write-Output "STATUS: $($r.StatusCode)"
Write-Output ""

# 8. Regresion: dashboard admin sigue funcionando
$r = Invoke-WebRequest -Uri "$base/admin/dashboard" -WebSession $sessAdmin -UseBasicParsing
Write-Output "== Regresion /admin/dashboard (esperado 200) =="
Write-Output "STATUS: $($r.StatusCode)"
