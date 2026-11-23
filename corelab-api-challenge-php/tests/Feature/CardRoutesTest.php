<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class CardRoutesTest extends TestCase
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
    public function it_can_list_all_cards()
    {
        $card = Card::factory()->create([
            'id_user' => $this->authUser->id_user,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->get('/api/cards');

        $response->assertStatus(200)
                 ->assertJson([
                     [
                         'id_user' => $card->id_user,
                         'color' => $card->color,
                         'content' => $card->content,
                         'title' => $card->title,
                         'favorite' => $card->favorite,
                     ]
                 ]);
    }

    /** @test */
    public function it_can_store_a_new_card()
    {
        $user = $this->authUser;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->post('/api/cards', [
            'id_user' => $user->id_user,
            'color' => 'blue',
            'content' => 'Content for the new card',
            'title' => 'New Card',
            'favorite' => true,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'id_user' => $user->id_user,
                     'color' => 'blue',
                     'content' => 'Content for the new card',
                     'title' => 'New Card',
                     'favorite' => true,
                 ]);
    }

    /** @test */
    public function it_can_show_a_card()
    {
        $card = Card::factory()->create([
            'id_user' => $this->authUser->id_user,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->get("/api/cards/{$card->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id_user' => $card->id_user,
                     'color' => $card->color,
                     'content' => $card->content,
                     'title' => $card->title,
                     'favorite' => $card->favorite,
                 ]);
    }

    /** @test */
    public function it_can_update_a_card()
    {
        $card = Card::factory()->create([
            'id_user' => $this->authUser->id_user,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->put("/api/cards/{$card->id}", [
            'id_user' => $card->id_user,
            'color' => 'red',
            'content' => 'Updated content',
            'title' => 'Updated Card Title',
            'favorite' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'id_user' => $card->id_user,
                     'color' => 'red',
                     'content' => 'Updated content',
                     'title' => 'Updated Card Title',
                     'favorite' => false,
                 ]);
    }

    /** @test */
    public function it_can_delete_a_card()
    {
        $card = Card::factory()->create([
            'id_user' => $this->authUser->id_user,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $this->authToken",
        ])->delete("/api/cards/{$card->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    /** @test */
    public function it_isolates_cards_between_users()
    {
        $other = User::factory()->create();
        $card = Card::factory()->create(['id_user' => $other->id_user]);
        $headers = ['Authorization' => "Bearer $this->authToken"];

        $this->withHeaders($headers)->get('/api/cards')->assertJsonMissing(['id' => $card->id]);
        $this->withHeaders($headers)->get("/api/cards/{$card->id}")->assertStatus(404);
        $payload = ['id_user' => $other->id_user, 'color' => 'blue', 'content' => 'teste', 'title' => 'teste', 'favorite' => false];
        $this->withHeaders($headers)->put("/api/cards/{$card->id}", $payload)->assertStatus(404);
        $this->withHeaders($headers)->delete("/api/cards/{$card->id}")->assertStatus(404);
        $this->withHeaders($headers)->post('/api/cards', $payload)
            ->assertStatus(201)->assertJson(['id_user' => $this->authUser->id_user]);
    }
}
