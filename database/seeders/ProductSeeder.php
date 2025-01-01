<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            [
                'name' => 'Spaghetti Carbonara',
                'description' => 'Creamy pasta with bacon and cheese',
                'price' => 25000,
                'stock' => 30,
                'image' => 'https://img.freepik.com/free-photo/pasta-plate-chopping-board-with-fork_23-2148357206.jpg?t=st=1719057728~exp=1719061328~hmac=14bd1b86f14f239ac0606207a4565cd161ad628d6871d2e9118b84b9a36bcd92&w=996',
                'is_best_seller' => 1,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Caesar Salad',
                'description' => 'Fresh salad with Caesar dressing',
                'price' => 18000,
                'stock' => 40,
                'image' => 'https://img.freepik.com/free-photo/salad_23-2147961007.jpg?t=st=1719057779~exp=1719061379~hmac=8e6a5fd8af6110ae9426e3d8735def5437f978b429d9279ea856947f16660cbd&w=1380',
                'is_best_seller' => 0,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Lemonade',
                'description' => 'Refreshing drink made from lemons',
                'price' => 7000,
                'stock' => 100,
                'image' => 'https://img.freepik.com/free-photo/iced-tea-with-lime-ice_114579-7146.jpg?t=st=1719057830~exp=1719061430~hmac=e663025993b3c82635838f06859c5cb9ea5d7807e3f7e14e8b2f1706c1f87628&w=1380',
                'is_best_seller' => 0,
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cappuccino',
                'description' => 'Espresso with steamed milk and foam',
                'price' => 15000,
                'stock' => 60,
                'image' => 'https://img.freepik.com/free-psd/coffee-cup-icon-isolated-3d-render-illustration_47987-8773.jpg?t=st=1719057250~exp=1719060850~hmac=74e24331a47d765ed15708126ea78dd13792528941927707d5bf7103de09e69f&w=826',
                'is_best_seller' => 1,
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fruit Salad',
                'description' => 'A mix of fresh and juicy fruits',
                'price' => 15000,
                'stock' => 50,
                'image' => 'https://img.freepik.com/free-photo/fresh-fruit-berry-salad-healthy-eating_114579-20466.jpg?t=st=1719059543~exp=1719063143~hmac=71d24b93845ca517af183f80376d8c726c7aaee19bf275b5b281268ee9e49bbd&w=1380',
                'is_best_seller' => 0,
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Chocolate Donut',
                'description' => 'Donut with chocolate glaze',
                'price' => 12000,
                'stock' => 30,
                'image' => 'https://img.freepik.com/free-photo/top-view-donuts-with-frosting_23-2148468151.jpg?t=st=1719057999~exp=1719061599~hmac=57865ab44901e252ca08708e2f7e52b3cba7f6c877cccd26ec30a3c0effa9f10&w=1060',
                'is_best_seller' => 1,
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Cold brew coffee with ice',
                'price' => 16000,
                'stock' => 80,
                'image' => 'https://img.freepik.com/free-photo/iced-coffee_1339-4593.jpg?t=st=1719058070~exp=1719061670~hmac=9ac10b420f23f522342c60b006e9cb74e26045cfa3dbfd2525c22249e7f5d334&w=1380',
                'is_best_seller' => 0,
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Grilled Chicken',
                'description' => 'Juicy grilled chicken with herbs',
                'price' => 30000,
                'stock' => 25,
                'image' => 'https://img.freepik.com/free-photo/baked-chicken-wings-asian-style-tomatoes-sauce-plate_2829-10654.jpg?t=st=1719058102~exp=1719061702~hmac=1d3902b81a2e43bdd4c797244c1d4fd1d538b6397daa64f8501d5ee0cd4839bd&w=1380',
                'is_best_seller' => 1,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Spring Rolls',
                'description' => 'Crispy spring rolls with vegetable filling',
                'price' => 11000,
                'stock' => 45,
                'image' => 'https://img.freepik.com/free-photo/fried-spring-rolls-cutting-board_1150-17010.jpg?t=st=1719058122~exp=1719061722~hmac=d8d3155dbdc5a05cabbdf37c6ae45dab3164a51f6cb44925d835b78c88e0b31d&w=1380',
                'is_best_seller' => 0,
                'category_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Orange Juice',
                'description' => 'Freshly squeezed orange juice',
                'price' => 8000,
                'stock' => 90,
                'image' => 'https://img.freepik.com/free-photo/fresh-orange-juice-glass-dark-background_1150-45560.jpg?t=st=1719058169~exp=1719061769~hmac=2a5ce232c4be21ef13c1e75a62ba4e6e5688af623af8ad374f8933310b8396ec&w=1380',
                'is_best_seller' => 1,
                'category_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
