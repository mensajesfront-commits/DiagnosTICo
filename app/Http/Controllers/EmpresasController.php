<?php

namespace App\Http\Controllers;

use App\Http\Requests\Empresas\DatosEmpresaRequest;
use App\Models\Diagnostico;
use App\Models\Empresa;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\EmpresaDesactivada;
use App\Services\Mediciones\AsignarMedicion;
use App\Support\DatosEmpresas;
use App\Support\Plazos;
use App\Support\Invitaciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A3 · Empresas (HU-031 a HU-035): lista, ficha (Historial y Datos de la
 * cuenta), registrar una empresa con su usuario principal, editar sus datos,
 * desactivar y reactivar. Permisos: `empresas.ver` para ver y
 * `empresas.registrar` para cambiar (docs/17_SEGURIDAD.md).
 */
class EmpresasController extends Controller
{
    public function __construct(private readonly AsignarMedicion $asignar) {}

    /** A3 · Lista con búsqueda, filtros y paginado (en la pantalla). */
    public function index(Request $request): Response
    {
        return Inertia::render('empresas/Index', [
            'empresas' => DatosEmpresas::lista(),
            'sectores' => Sector::orderBy('nombre')->get(['id', 'nombre']),
            // "Ver las N empresas en Empresas" desde A2 llega con ?sector=.
            'sectorInicial' => $request->integer('sector') ?: null,
        ]);
    }

    /** A3.2 · Registrar empresa. */
    public function crear(): Response
    {
        return Inertia::render('empresas/Registrar', [
            'opciones' => DatosEmpresas::opciones(),
            'fechaMinima' => Plazos::fechaMinima(null),
        ]);
    }

    /**
     * Crea la empresa y su usuario principal (rol Empresa, sin contraseña)
     * y le envía el correo para crearla (RN-006). Si se pidió, asigna la
     * primera medición en la misma operación (HU-032 CA-003).
     */
    public function guardar(DatosEmpresaRequest $request): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        [$empresa, $usuario] = DB::transaction(function () use ($request, $admin): array {
            $empresa = Empresa::create([...$request->datosEmpresa(), 'registrada_por' => $admin->id, 'activa' => true]);
            $usuario = User::create([...$request->datosUsuario(), 'empresa_id' => $empresa->id, 'password' => null]);
            $usuario->assignRole('Empresa');

            if ($request->boolean('asignar_medicion')) {
                $diagnostico = Diagnostico::findOrFail($request->integer('diagnostico_id'));
                $this->asignar->asignar($empresa, $diagnostico, $request->input('fecha_limite'), null, false, $admin);
            }

            return [$empresa, $usuario];
        });

        Invitaciones::enviar($usuario, 'Empresa', null, $admin->name);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Empresa «{$empresa->nombre}» registrada. Enviamos a {$usuario->email} el enlace para crear su contraseña."]);

        return to_route('empresas.ver', $empresa);
    }

    /** A3.1 · Ficha de la empresa (pestañas Historial y Datos de la cuenta). */
    public function ver(Empresa $empresa): Response
    {
        return Inertia::render('empresas/Ver', [
            ...DatosEmpresas::ficha($empresa),
            'opciones' => DatosEmpresas::opciones(),
        ]);
    }

    /**
     * A3.1d · Guarda los datos de la empresa y de su usuario principal.
     * Cambiar el sector no toca las mediciones hechas (HU-034 CA-002). Si
     * cambia el correo, se cierran las sesiones del usuario.
     */
    public function actualizar(DatosEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        DB::transaction(function () use ($request, $empresa): void {
            $empresa->update($request->datosEmpresa());
            $principal = DatosEmpresas::principal($empresa);

            if ($principal) {
                $datos = $request->datosUsuario();
                $cambiaCorreo = $datos['email'] !== $principal->email;
                $principal->update($datos);

                if ($cambiaCorreo) {
                    DB::table('sessions')->where('user_id', $principal->id)->delete();
                    $principal->forceFill(['remember_token' => null])->save();
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos de la cuenta guardados.']);

        return back();
    }

    /**
     * A3.1f · Nadie de la empresa entra (RN-025); sus mediciones y
     * resultados se conservan. Motivo y aviso por correo opcionales.
     */
    public function desactivar(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
            'avisar' => ['boolean'],
        ]);

        $principal = DatosEmpresas::principal($empresa);

        DB::transaction(function () use ($empresa, $principal, $datos): void {
            $empresa->update(['activa' => false, 'desactivada_en' => now(), 'motivo_desactivacion' => $datos['motivo'] ?? null]);
            $principal?->forceFill(['activo' => false])->save();
            DB::table('sessions')->whereIn('user_id', $empresa->usuarios()->pluck('id'))->delete();
        });

        if (($datos['avisar'] ?? false) && $principal) {
            $principal->notify(new EmpresaDesactivada($empresa, $datos['motivo'] ?? null));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cuenta de «{$empresa->nombre}» desactivada."]);

        return back();
    }

    /** Reactivar desde la misma ficha (HU-035 CA-006). */
    public function reactivar(Empresa $empresa): RedirectResponse
    {
        DB::transaction(function () use ($empresa): void {
            $empresa->update(['activa' => true, 'desactivada_en' => null, 'motivo_desactivacion' => null]);
            DatosEmpresas::principal($empresa)?->forceFill(['activo' => true])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "Cuenta de «{$empresa->nombre}» reactivada."]);

        return back();
    }
}
