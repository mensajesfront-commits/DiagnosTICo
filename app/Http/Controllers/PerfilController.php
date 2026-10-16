<?php

namespace App\Http\Controllers;

use App\Http\Requests\Perfil\ActualizarPerfilRequest;
use App\Http\Requests\Perfil\CambiarContrasenaRequest;
use App\Models\ActividadEconomica;
use App\Models\Empresa;
use App\Models\User;
use App\Services\Acceso\CodigoVerificacion;
use App\Support\OpcionesPerfil;
use App\Support\Ubicaciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A6 · Mi perfil del Administrador y E11 · Mi perfil de la empresa.
 */
class PerfilController extends Controller
{
    public function __construct(private readonly CodigoVerificacion $codigos) {}

    public function edit(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $empresa = $usuario->empresa()->with('sector:id,nombre')->first();
        $rol = $usuario->getRoleNames()->first();

        return Inertia::render('perfil/MiPerfil', [
            'status' => $request->session()->get('status'),
            'usuario' => [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'cargo' => $usuario->cargo,
                'telefono' => $usuario->telefono,
                'ciudad' => $usuario->ciudad,
                'departamento' => $usuario->departamento,
                'pais' => $usuario->pais,
                'zona_horaria' => $usuario->zona_horaria,
                'idioma' => $usuario->idioma,
                'avisos' => OpcionesPerfil::avisosDe($usuario),
                'foto_url' => $this->urlImagen($usuario, $empresa),
                'ultimo_acceso_en' => $usuario->ultimo_acceso_en?->toIso8601String(),
                'creado_en' => $usuario->created_at?->toIso8601String(),
                'contrasena_actualizada_en' => ($usuario->contrasena_actualizada_en ?? $usuario->created_at)?->toIso8601String(),
                // Correo nuevo que espera el código (abre la ventana de verificación).
                'email_pendiente' => $usuario->email_pendiente,
            ],
            'rol' => $rol,
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre,
                'sector' => $empresa->sector?->nombre,
                'actividad_economica_id' => $empresa->actividad_economica_id,
                'descripcion' => $empresa->descripcion,
                'ciudad' => $empresa->ciudad,
                'departamento' => $empresa->departamento,
                'pais' => $empresa->pais,
                'sitio_web' => $empresa->sitio_web,
                'numero_empleados' => $empresa->numero_empleados,
            ] : null,
            'editaEmpresa' => $empresa !== null && $rol === 'Empresa',
            'opciones' => [
                'paises' => Ubicaciones::paises(),
                'zonas' => OpcionesPerfil::zonasHorarias($usuario->zona_horaria),
                'idiomas' => OpcionesPerfil::IDIOMAS,
                'empleados' => OpcionesPerfil::RANGOS_EMPLEADOS,
                // Actividades (CIIU) del sector de la empresa; la actual va
                // aunque esté inactiva, para que se siga viendo (DEC-015).
                'actividades' => $empresa === null ? [] : ActividadEconomica::query()
                    ->where('sector_id', $empresa->sector_id)
                    ->where(fn ($q) => $q->where('activo', true)->orWhere('id', $empresa->actividad_economica_id))
                    ->orderBy('codigo')
                    ->get(['id', 'codigo', 'nombre'])
                    ->toArray(),
                'avisos' => array_map(fn (array $aviso) => $aviso[0], OpcionesPerfil::avisosPara($usuario)),
            ],
        ]);
    }

    public function update(ActualizarPerfilRequest $request): RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $datos = $request->validated();

        $usuario->fill([
            'name' => $datos['name'],
            'cargo' => $datos['cargo'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
            'departamento' => $datos['departamento'] ?? null,
            'pais' => $datos['pais'] ?? null,
            'zona_horaria' => $datos['zona_horaria'],
            'idioma' => $datos['idioma'],
            'avisos' => array_intersect_key(
                array_map(boolval(...), $datos['avisos'] ?? []),
                OpcionesPerfil::avisosPara($usuario),
            ),
        ]);

        // El correo nuevo no se guarda todavía: le llega un código y se
        // confirma en la ventana de verificación (DEC-020). Se sigue entrando
        // con el anterior hasta confirmarlo.
        $correoNuevo = mb_strtolower(trim((string) $datos['email']));
        $cambiaCorreo = $correoNuevo !== mb_strtolower($usuario->email);
        $usuario->email_pendiente = $cambiaCorreo ? $correoNuevo : null;
        $usuario->save();

        if ($cambiaCorreo) {
            $this->codigos->enviar($usuario, $correoNuevo);
        }

        if ($request->editaEmpresa() && $usuario->empresa) {
            $usuario->empresa->update([
                'nombre' => $datos['empresa']['nombre'],
                'actividad_economica_id' => $datos['empresa']['actividad_economica_id'] ?? null,
                'descripcion' => $datos['empresa']['descripcion'],
                'ciudad' => $datos['empresa']['ciudad'],
                'departamento' => $datos['empresa']['departamento'],
                'pais' => $datos['empresa']['pais'],
                'sitio_web' => $datos['empresa']['sitio_web'] ?? null,
                'numero_empleados' => $datos['empresa']['numero_empleados'] ?? null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $cambiaCorreo
            ? "Cambios guardados. Para cambiar el correo, escribe el código que enviamos a {$correoNuevo}."
            : 'Cambios guardados.']);

        return to_route('profile.edit');
    }

    /** Confirma el correo nuevo con el código que le llegó. */
    public function verificarCorreo(Request $request): RedirectResponse
    {
        $datos = $request->validate(
            ['codigo' => ['required', 'digits:6']],
            ['codigo.required' => 'Escribe el código de 6 dígitos.', 'codigo.digits' => 'El código tiene 6 dígitos.'],
        );

        /** @var User $usuario */
        $usuario = $request->user();
        $nuevo = $usuario->email_pendiente;

        if ($nuevo === null) {
            return to_route('profile.edit');
        }

        // Otra cuenta pudo tomar el correo mientras tanto.
        if (User::withTrashed()->where('email', $nuevo)->whereKeyNot($usuario->id)->exists()) {
            $usuario->forceFill(['email_pendiente' => null])->save();

            return back()->withErrors(['email' => 'Ya hay una cuenta con este correo.']);
        }

        $error = $this->codigos->comprobar($usuario, $datos['codigo']);

        if ($error !== null) {
            return back()->withErrors(['codigo' => $error]);
        }

        $usuario->forceFill(['email' => $nuevo, 'email_pendiente' => null, 'email_verified_at' => now()])->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => "Listo: tu correo ahora es {$nuevo}."]);

        return to_route('profile.edit');
    }

    public function reenviarCodigoCorreo(Request $request): RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if ($usuario->email_pendiente !== null) {
            $this->codigos->enviar($usuario, $usuario->email_pendiente);
        }

        return back()->with('status', 'Te enviamos un código nuevo. Revisa también la carpeta de spam.');
    }

    /** Cerrar la ventana: el correo sigue siendo el anterior. */
    public function cancelarCambioCorreo(Request $request): RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $usuario->forceFill(['email_pendiente' => null, 'codigo_verificacion' => null, 'codigo_vence_en' => null])->save();

        return to_route('profile.edit');
    }

    public function contrasena(CambiarContrasenaRequest $request): RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        $usuario->forceFill([
            'password' => $request->string('password')->value(),
            'contrasena_actualizada_en' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contraseña actualizada. Tu sesión sigue abierta.']);

        return back();
    }

    /**
     * "Cambiar foto" (A6) o "Cambiar logo" (E11): la cuenta principal de una
     * empresa cambia el logo de la empresa; las demás cuentas, su foto.
     */
    public function foto(Request $request): RedirectResponse
    {
        $request->validate(
            ['foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            [],
            ['foto' => 'imagen'],
        );

        /** @var User $usuario */
        $usuario = $request->user();
        $empresa = $usuario->hasRole('Empresa') ? $usuario->empresa : null;
        $ruta = $request->file('foto')?->store($empresa ? 'logos' : 'fotos');

        if ($empresa) {
            $anterior = $empresa->logo_ruta;
            $empresa->forceFill(['logo_ruta' => $ruta])->save();
        } else {
            $anterior = $usuario->foto_ruta;
            $usuario->forceFill(['foto_ruta' => $ruta])->save();
        }

        if ($anterior) {
            Storage::delete($anterior);
        }

        $esLogo = $empresa !== null;

        Inertia::flash('toast', ['type' => 'success', 'message' => $esLogo ? 'Logo actualizado.' : 'Foto actualizada.']);

        return back();
    }

    /**
     * Sirve la foto o el logo sin publicar la carpeta de archivos. Lo ve la
     * misma cuenta, su empresa o quien puede ver empresas o usuarios.
     */
    public function imagen(Request $request, string $tipo, int $id): StreamedResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if ($tipo === 'empresa') {
            $ruta = Empresa::findOrFail($id)->logo_ruta;
            $propia = $usuario->empresa_id === $id;
        } else {
            $ruta = User::findOrFail($id)->foto_ruta;
            $propia = $usuario->id === $id;
        }

        abort_unless($propia || $usuario->can('empresas.ver') || $usuario->can('usuarios.ver'), 403);
        abort_unless($ruta && Storage::exists($ruta), 404);

        return Storage::response($ruta);
    }

    private function urlImagen(User $usuario, ?Empresa $empresa): ?string
    {
        if ($empresa && $usuario->hasRole('Empresa')) {
            return $empresa->logo_ruta
                ? route('perfil.imagen', ['tipo' => 'empresa', 'id' => $empresa->id, 'v' => md5($empresa->logo_ruta)])
                : null;
        }

        return $usuario->foto_ruta
            ? route('perfil.imagen', ['tipo' => 'usuario', 'id' => $usuario->id, 'v' => md5($usuario->foto_ruta)])
            : null;
    }
}
