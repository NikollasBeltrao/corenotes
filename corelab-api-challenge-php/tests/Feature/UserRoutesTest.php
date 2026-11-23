<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

class UserRoutesTest extends TestCase
{
    use RefreshDatabase, TestHelper;

    private string $authToken;
    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authToken = $this->getAuthToken();
        $this->authUser = PersonalAccessToken::findToken($this->authToken)->tokenable;
    }

    /** @test */
    public function it_can_list_all_users()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->get('/api/users');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_store_a_new_user()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->post('/api/users', [
            'login' => 'newuser@example.com',
            'password' => 'password',
            'name' => 'New User',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'login' => 'newuser@example.com',
                'name' => 'New User',
            ]);
    }

    /** @test */
    public function it_can_show_a_user()
    {
        $user = $this->authUser;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->get("/api/users/{$user->id_user}");

        $response->assertStatus(200)
            ->assertJson([
                'id_user' => $user->id_user,
                'login' => $user->login,
                'name' => $user->name,
            ]);
    }

    /** @test */
    public function it_can_update_a_user()
    {
        $user = $this->authUser;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->put("/api/users/{$user->id_user}", [
            'login' => 'updateduser@example.com',
            'password' => 'newpassword',
            'name' => 'Updated User',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id_user' => $user->id_user,
                'login' => 'updateduser@example.com',
                'name' => 'Updated User',
            ]);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = $this->authUser;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->delete("/api/users/{$user->id_user}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id_user' => $user->id_user]);
    }

    /** @test */
    public function it_prevents_access_to_other_users()
    {
        $other = User::factory()->create();
        $headers = ['Authorization' => "Bearer $this->authToken"];

        $this->withHeaders($headers)->get('/api/users')->assertJsonMissing(['id_user' => $other->id_user]);
        $this->withHeaders($headers)->get("/api/users/{$other->id_user}")->assertStatus(404);
        $this->withHeaders($headers)->put("/api/users/{$other->id_user}", [
            'name' => 'Alterado', 'login' => 'alterado@example.com',
        ])->assertStatus(404);
        $this->withHeaders($headers)->delete("/api/users/{$other->id_user}")->assertStatus(404);
    }

    /** @test */
    public function it_does_not_allow_reusing_another_users_login()
    {
        $other = User::factory()->create();
        $headers = ['Authorization' => "Bearer $this->authToken", 'Accept' => 'application/json'];

        $this->withHeaders($headers)->put("/api/users/{$this->authUser->id_user}", [
            'name' => 'Updated', 'login' => $other->login,
        ])->assertStatus(422)->assertJsonValidationErrors('login');

        $this->withHeaders($headers)->put("/api/users/{$this->authUser->id_user}", [
            'name' => 'Updated', 'login' => $this->authUser->login,
        ])->assertStatus(200);
    }
}
