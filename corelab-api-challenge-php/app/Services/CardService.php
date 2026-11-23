<?php

namespace App\Services;

use App\Models\Card;
use App\Services\Interfaces\CardServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class CardService implements CardServiceInterface
{

    public function getAllCards(Request $request): Collection
    {
        $parameters = $request->query();
        $query = Card::query()->where('id_user', Auth::id());
        foreach ($parameters as $key => $value) {
            if (Schema::hasColumn('cards', $key)) {
                $query->where($key, $value);
            }
        }
        return $query->get();
    }

    public function createCard(array $data): Card
    {
        $data['id_user'] = Auth::id();
        return Card::create($data);
    }

    public function getCardById(int $id): ?Card
    {
        return Card::where('id_user', Auth::id())->find($id);
    }

    public function updateCard(array $data, int $id): Card
    {
        $card = Card::where('id_user', Auth::id())->findOrFail($id);
        $data['id_user'] = Auth::id();
        $card->update($data);
        return $card;
    }

    public function deleteCard(int $id): bool
    {
        return Card::where('id_user', Auth::id())->whereKey($id)->delete() > 0;
    }
}
