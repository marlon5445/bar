<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'usuario',
        'password',
        'rol',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    /**
     * Obtener usuario por su nombre de usuario
     */
    public function obtenerPorUsuario(string $usuario): ?array
    {
        return $this->where('usuario', $usuario)->first();
    }

    /**
     * Obtener los nombres de los permisos asignados a un rol determinado
     */
    public function obtenerPermisosPorRol(string $rolNombre): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('permisos p');
        $builder->select('p.nombre');
        $builder->join('rol_permisos rp', 'rp.permiso_id = p.id');
        $builder->join('roles r', 'r.id = rp.rol_id');
        $builder->where('r.nombre', $rolNombre);

        $query = $builder->get();
        $resultado = $query->getResultArray();

        return array_column($resultado, 'nombre');
    }

    /**
     * Verificar si un rol tiene un permiso específico
     */
    public function rolTienePermiso(string $rolNombre, string $permisoNombre): bool
    {
        // El ADMIN siempre tiene acceso a todo por defecto
        if ($rolNombre === 'ADMIN') {
            return true;
        }

        $permisos = $this->obtenerPermisosPorRol($rolNombre);
        return in_array($permisoNombre, $permisos, true);
    }
}
