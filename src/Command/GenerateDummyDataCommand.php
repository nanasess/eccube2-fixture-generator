<?php

namespace Eccube2\Command;

use Eccube2\Init;
use Eccube2\Tests\Fixture\Generator;
use Faker\Factory as Faker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateDummyDataCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'eccube:fixtures:generate';

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();
    }

    protected function configure()
    {
        $this
            ->setDescription('Dummy data generator')
            ->addOption('with-locale', null, InputOption::VALUE_REQUIRED, 'Set to the locale.', 'ja_JP')
            ->addOption('products', null, InputOption::VALUE_REQUIRED, 'Number of Products.', 100)
            ->addOption('orders', null, InputOption::VALUE_REQUIRED, 'Number of Orders.', 10)
            ->addOption('customers', null, InputOption::VALUE_REQUIRED, 'Number of Customers.', 100)
            ->setHelp(<<<EOF
The <info>%command.name%</info> command generate of dummy data.

  <info>php %command.full_name%</info>
;
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('with-locale');
        $numberOfProducts = $input->getOption('products');
        $numberOfOrder = $input->getOption('orders');
        $numberOfCustomer = $input->getOption('customers');

        /** @var \SC_Query $objQuery */
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        /** @var Generator $objGenerator */
        $objGenerator = new Generator($objQuery, $locale);
        /** @var \Faker\Generator $faker */
        $faker = Faker::create($locale);
        $num = $objQuery->count('dtb_customer');
        if ($num < $numberOfCustomer) {
            $num = $numberOfCustomer - $num;
            $io->write('Generating Customers');
            for ($i = 0; $i < $num; $i++) {
                $objGenerator->createCustomer();
                $io->write('.');
            }
            $objGenerator->createCustomer(null, ['status' => '1']); // non-active member
            $output->writeln('.');
        }

        $num = $objQuery->count('dtb_products');
        $product_ids = [];
        // 受注生成件数 + 初期データの商品が生成されているはず
        if ($num < ($numberOfProducts + 2)) {
            $io->write('Generating Products');
            // 規格なしも含め Generating Products の分だけ生成する
            for ($i = 0; $i < $numberOfProducts - 1; $i++) {
                $product_ids[] = $objGenerator->createProduct();
                $io->write('.');
            }
            $product_ids[] = $objGenerator->createProduct('規格なし商品', 0);
            $io->writeln('.');

            $category_ids = [];
            // 5件以上のカテゴリを生成する
            do {
                $category_ids = array_merge($category_ids, $objGenerator->createCategories());
            } while (\count($category_ids) < 5);

            foreach ($product_ids as $product_id) {
                $num = $faker->numberBetween(2, \count($category_ids) - 1);
                $objGenerator->relateProductCategories($product_id, array_rand(array_flip($category_ids), $num >= 2 ? $num : 2));
            }
            $objDb = new \SC_Helper_DB_Ex();
            $objDb->sfCountCategory($objQuery);
        }

        $num = $objQuery->count('dtb_order');
        $objQuery->setLimit($numberOfCustomer);
        $customer_ids = $objQuery->getCol('customer_id', 'dtb_customer', 'del_flg = 0');
        array_unshift($customer_ids, '0'); // 非会員の注文を追加する
        $objQuery->setLimit(10);
        $product_class_ids = $objQuery->getCol('product_class_id', 'dtb_products_class', 'del_flg = 0');
        if ($num < $numberOfOrder) {
            $io->write('Generating Orders');
            foreach ($customer_ids as $customer_id) {
                $target_product_class_ids = array_rand(array_flip($product_class_ids), $faker->numberBetween(2, \count($product_class_ids) - 1));
                $charge = $faker->randomNumber(4);
                $discount = $faker->numberBetween(0, $charge);
                $order_count_per_customer = $objQuery->count('dtb_order', 'customer_id = ?', [$customer_id]);
                for ($i = $order_count_per_customer; $i < $numberOfOrder / \count($customer_ids); $i++) {
                    // キャンセルと決済処理中は除外して注文を生成する
                    $target_statuses = [ORDER_NEW, ORDER_PAY_WAIT, ORDER_PRE_END, ORDER_BACK_ORDER, ORDER_DELIV];
                    $order_status_id = $target_statuses[$faker->numberBetween(0, \count($target_statuses) - 1)];
                    $objGenerator->createOrder($customer_id, $target_product_class_ids, 1, $charge, $discount, $order_status_id);
                    $io->write('.');
                }
            }
            $io->writeln('');

            return 0;
        }

        return 0;
    }
}
