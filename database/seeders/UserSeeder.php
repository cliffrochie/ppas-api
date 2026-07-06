<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleRequester = Role::where('name', 'requester')->value('id');
        $roleProcurement = Role::where('name', 'procurement_officer')->value('id');
        $roleBacSecretariat = Role::where('name', 'bac_secretariat')->value('id');
        $roleBudget = Role::where('name', 'budget_officer')->value('id');

        $orm = Office::where('code', 'ORM')->value('id');
        $bac = Office::where('code', 'BAC')->value('id');
        $as = Office::where('code', 'AS')->value('id');
        $fs = Office::where('code', 'FS')->value('id');
        $es = Office::where('code', 'ES')->value('id');
        $os = Office::where('code', 'OS')->value('id');

        $users = [
            // ── Procurement Officers (BAC) ────────────────────────────────────
            ['first_name' => 'Juan',      'middle_name' => 'Santos',    'last_name' => 'Dela Cruz',   'email' => 'procurement@nia-caraga.test',          'role_id' => $roleProcurement, 'office_id' => $bac],
            ['first_name' => 'Rodrigo',   'middle_name' => 'Flores',    'last_name' => 'Villanueva',  'email' => 'procurement2@nia-caraga.test',         'role_id' => $roleProcurement, 'office_id' => $bac],
            ['first_name' => 'Maricris',  'middle_name' => 'Aguilar',   'last_name' => 'Borja',       'email' => 'procurement3@nia-caraga.test',         'role_id' => $roleProcurement, 'office_id' => $bac],
            ['first_name' => 'Emmanuel',  'middle_name' => 'Pascual',   'last_name' => 'Cabrera',     'email' => 'procurement4@nia-caraga.test',         'role_id' => $roleProcurement, 'office_id' => $bac],
            ['first_name' => 'Lourdes',   'middle_name' => 'Castillo',  'last_name' => 'Ramos',       'email' => 'procurement5@nia-caraga.test',         'role_id' => $roleProcurement, 'office_id' => $bac],

            // ── BAC Secretariat (BAC) ─────────────────────────────────────────
            ['first_name' => 'Herminia',  'middle_name' => 'Salonga',   'last_name' => 'Villareal',   'email' => 'bac.secretariat@nia-caraga.test',      'role_id' => $roleBacSecretariat, 'office_id' => $bac],
            ['first_name' => 'Restituto', 'middle_name' => 'Gatchalian', 'last_name' => 'Ocampo',      'email' => 'bac.secretariat2@nia-caraga.test',     'role_id' => $roleBacSecretariat, 'office_id' => $bac],
            ['first_name' => 'Adoracion', 'middle_name' => 'Nepomuceno', 'last_name' => 'Del Rosario', 'email' => 'bac.secretariat3@nia-caraga.test',     'role_id' => $roleBacSecretariat, 'office_id' => $bac],

            // ── Budget Officers (AS) ──────────────────────────────────────────
            ['first_name' => 'Maria',     'middle_name' => 'Reyes',     'last_name' => 'Santos',      'email' => 'budget@nia-caraga.test',               'role_id' => $roleBudget,      'office_id' => $as],
            ['first_name' => 'Rosario',   'middle_name' => 'Espiritu',  'last_name' => 'Navarro',     'email' => 'budget2@nia-caraga.test',              'role_id' => $roleBudget,      'office_id' => $as],
            ['first_name' => 'Fernando',  'middle_name' => 'Beltran',   'last_name' => 'Aquino',      'email' => 'budget3@nia-caraga.test',              'role_id' => $roleBudget,      'office_id' => $as],
            ['first_name' => 'Teresita',  'middle_name' => 'Marcos',    'last_name' => 'Ocampo',      'email' => 'budget4@nia-caraga.test',              'role_id' => $roleBudget,      'office_id' => $as],
            ['first_name' => 'Alfredo',   'middle_name' => 'Domingo',   'last_name' => 'Magno',       'email' => 'budget5@nia-caraga.test',              'role_id' => $roleBudget,      'office_id' => $as],

            // ── Requesters — Finance Section (FS) ────────────────────────────
            ['first_name' => 'Carlos',    'middle_name' => 'Lopez',     'last_name' => 'Reyes',       'email' => 'requester.ord@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Angelica',  'middle_name' => 'Delos Reyes', 'last_name' => 'Fuentes',    'email' => 'requester.fs2@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Renato',    'middle_name' => 'Villafuerte', 'last_name' => 'Perez',      'email' => 'requester.fs3@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Corazon',   'middle_name' => 'Evangelista', 'last_name' => 'Roque',      'email' => 'requester.fs4@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Danilo',    'middle_name' => 'Herrera',   'last_name' => 'Salazar',     'email' => 'requester.fs5@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Natividad', 'middle_name' => 'Abad',      'last_name' => 'Bautista',    'email' => 'requester.fs6@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Gregorio',  'middle_name' => 'Tolentino', 'last_name' => 'Diaz',        'email' => 'requester.fs7@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],
            ['first_name' => 'Luzviminda', 'middle_name' => 'Mercado',   'last_name' => 'Zamora',      'email' => 'requester.fs8@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $fs],

            // ── Requesters — Engineering Section (ES) ────────────────────────
            ['first_name' => 'Ana',       'middle_name' => 'Cruz',      'last_name' => 'Mendoza',     'email' => 'requester.ed@nia-caraga.test',         'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Jose',      'middle_name' => 'Barrientos', 'last_name' => 'Pascual',    'email' => 'requester.es2@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Cristina',  'middle_name' => 'Soriano',   'last_name' => 'Enriquez',    'email' => 'requester.es3@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Roberto',   'middle_name' => 'Padilla',   'last_name' => 'Guerrero',    'email' => 'requester.es4@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Maricel',   'middle_name' => 'Dela Torre', 'last_name' => 'Sison',      'email' => 'requester.es5@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Ernesto',   'middle_name' => 'Caguioa',   'last_name' => 'Valdez',      'email' => 'requester.es6@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Florencia', 'middle_name' => 'Abrera',    'last_name' => 'Manalo',      'email' => 'requester.es7@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],
            ['first_name' => 'Norberto',  'middle_name' => 'Buenaventura', 'last_name' => 'Castillo',  'email' => 'requester.es8@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $es],

            // ── Requesters — Operation Section (OS) ──────────────────────────
            ['first_name' => 'Ricardo',   'middle_name' => 'Garcia',    'last_name' => 'Lim',         'email' => 'requester.fd@nia-caraga.test',         'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Sofia',     'middle_name' => 'Bautista',  'last_name' => 'Torres',      'email' => 'requester.ict@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Melanie',   'middle_name' => 'Coronel',   'last_name' => 'Quisumbing',  'email' => 'requester.os3@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Bernardo',  'middle_name' => 'Infante',   'last_name' => 'Santiago',    'email' => 'requester.os4@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Carmela',   'middle_name' => 'Palma',     'last_name' => 'Dela Vega',   'email' => 'requester.os5@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Honesto',   'middle_name' => 'Manalac',   'last_name' => 'Ibarra',      'email' => 'requester.os6@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Remedios',  'middle_name' => 'Sarmiento', 'last_name' => 'Alcantara',   'email' => 'requester.os7@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],
            ['first_name' => 'Victorino', 'middle_name' => 'Halili',    'last_name' => 'Crisostomo',  'email' => 'requester.os8@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $os],

            // ── Requesters — Administrative Section (AS) ─────────────────────
            ['first_name' => 'Milagros',  'middle_name' => 'Concepcion', 'last_name' => 'Panganiban',  'email' => 'requester.as1@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $as],
            ['first_name' => 'Arturo',    'middle_name' => 'Vergara',   'last_name' => 'Aragon',      'email' => 'requester.as2@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $as],
            ['first_name' => 'Edna',      'middle_name' => 'Lacsamana', 'last_name' => 'Buenaobra',   'email' => 'requester.as3@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $as],
            ['first_name' => 'Wilfredo',  'middle_name' => 'Alagon',    'last_name' => 'Tamayo',      'email' => 'requester.as4@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $as],
            ['first_name' => 'Perla',     'middle_name' => 'Dalisay',   'last_name' => 'Macapagal',   'email' => 'requester.as5@nia-caraga.test',        'role_id' => $roleRequester,   'office_id' => $as],

            // ── Requesters — Office of the Regional Manager (ORM) ────────────
            ['first_name' => 'Leandro',   'middle_name' => 'Tañedo',    'last_name' => 'Corpuz',      'email' => 'requester.orm1@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $orm],
            ['first_name' => 'Concepcion', 'middle_name' => 'Quiño',     'last_name' => 'Ferrer',      'email' => 'requester.orm2@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $orm],
            ['first_name' => 'Arsenio',   'middle_name' => 'Badillo',   'last_name' => 'Maniquis',    'email' => 'requester.orm3@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $orm],
            ['first_name' => 'Ligaya',    'middle_name' => 'Chua',      'last_name' => 'Evangelio',   'email' => 'requester.orm4@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $orm],
            ['first_name' => 'Domingo',   'middle_name' => 'Nieva',     'last_name' => 'Sioco',       'email' => 'requester.orm5@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $orm],

            // ── Requesters — BAC ──────────────────────────────────────────────
            ['first_name' => 'Rowena',    'middle_name' => 'Bayani',    'last_name' => 'Dizon',       'email' => 'requester.bac1@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $bac],
            ['first_name' => 'Aurelio',   'middle_name' => 'Macalinao', 'last_name' => 'Serrano',     'email' => 'requester.bac2@nia-caraga.test',       'role_id' => $roleRequester,   'office_id' => $bac],
        ];

        $defaults = [
            'extension_name' => null,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                array_merge($defaults, $user),
            );
        }
    }
}
