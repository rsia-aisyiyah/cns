<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class AesUserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new database user provider.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherContract $hasher, $model)
    {
        $this->hasher = $hasher; // meskipun kamu gak pakai
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->select(
                DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") as id_user'),
            )
            ->where('id_user', DB::raw('AES_ENCRYPT("' . $identifier . '", "' . env('MYSQL_AES_KEY_IDUSER') . '")'))
            ->first();
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token)
    {
        dd(__CLASS__ . '::' . __FUNCTION__, $identifier, $token);
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token)
    {
        dd(__CLASS__ . '::' . __FUNCTION__, $user, $token);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && \Illuminate\Support\Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return;
        }

        $query = $this->newModelQuery();

        $query->select(DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") as id_user'))
            ->where('id_user', DB::raw('AES_ENCRYPT("' . $credentials['username'] . '", "' . env('MYSQL_AES_KEY_IDUSER') . '")'))
            ->where('password', DB::raw('AES_ENCRYPT("' . $credentials['password'] . '", "' . env('MYSQL_AES_KEY_PASSWORD') . '")'));

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && \Illuminate\Support\Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return false;
        }

        // Ambil password terenkripsi dari database
        $query = $this->newModelQuery();
        $query->select(DB::raw('AES_DECRYPT(password, "' . env('MYSQL_AES_KEY_PASSWORD') . '") as password_plain'))
            ->where('id_user', DB::raw('AES_ENCRYPT("' . $credentials['username'] . '", "' . env('MYSQL_AES_KEY_IDUSER') . '")'));

        $passwordDecrypted = $query->first();

        if (!$passwordDecrypted) {
            return false;
        }

        unset($user->password);

        return $credentials['password'] === $passwordDecrypted->password_plain;
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {
        // Jika tidak dipaksa dan key AES tidak berubah, skip
        if (!$force && !env('MYSQL_AES_KEY_PASSWORD_REHASH')) {
            return;
        }

        // Dapatkan ID terenkripsi user
        $idUserEncrypted = DB::raw('AES_ENCRYPT("' . $credentials['username'] . '", "' . env('MYSQL_AES_KEY_IDUSER') . '")');

        // Update password terenkripsi dengan kunci baru
        DB::table($this->createModel()->getTable())
            ->where('id_user', $idUserEncrypted)
            ->update([
                'password' => DB::raw('AES_ENCRYPT("' . $credentials['password'] . '", "' . env('MYSQL_AES_KEY_PASSWORD') . '")'),
            ]);
    }






    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }
    }

    /**
     * Get a new query builder for the model instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function newModelQuery($model = null)
    {
        return is_null($model) ? $this->createModel()->newQuery()
            : $model->newQuery();
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }
}
