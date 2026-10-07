<?php

namespace App\Http\Controllers;

use App\Http\Requests\Perfil\ActualizarPerfilRequest;
use App\Http\Requests\Perfil\CambiarContrasenaRequest;
use App\Models\Empresa;
use App\Models\User;
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
    public function edit(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $empresa = $usuario->empresa()->with('sector:id,nombre')->first();
        $rol = $usuario->getRoleNames()->first();

        return Inertia::render('perfil/MiPerfil', [
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
            ],
            'rol' => $rol,
            'empresa' => $empresa ? [
                'nombre' => $empresa->nombre,
                'sector' => $empresa->sector?->nombre,
                'ciudad' => $empresa->ciudad,
                'departamento' => $empresa->departamento,
                'pais' => $empresa->pais,
                'sitio_web' => $empresa->sitio_web,
                'numero_empleados' => $empresa->numero_empleados,
            ] : null,
            'editaEmpresa' => $empresa !== null && $rol === 'Empresa',
            'opciones' => [
                'paises' => Ubicaciones::paises(),
                'zonas' => OpcionesPerfil::ZONAS_HORARIAS,
                'idiomas' => OpcionesPerfil::IDIOMAS,
                'empleados' => OpcionesPerfil::RANGOS_EMPLEADOS,
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
            'email' => $datos['email'],
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

        // [FUNCIONALIDAD POR DEFINIR] E11 dice que al cambiar el correo se
        // envía un enlace para verificarlo. La verificación está quitada
        // (DEC-012), así que por ahora el cambio se guarda directo.
        $usuario->save();

        if ($request->editaEmpresa() && $usuario->empresa) {
            $usuario->empresa->update([
                'nombre' => $datos['empresa']['nombre'],
                'ciudad' => $datos['empresa']['ciudad'],
                'departamento' => $datos['empresa']['departamento'],
                'pais' => $datos['empresa']['pais'],
                'sitio_web' => $datos['empresa']['sitio_web'] ?? null,
                'numero_empleados' => $datos['empresa']['numero_empleados'] ?? null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cambios guardados.']);

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
