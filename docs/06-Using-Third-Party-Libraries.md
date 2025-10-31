## Using Third-Party Libraries in BaleroCMS

BaleroCMS is ready to use Composer for dependencies. Integrating Third-Party Libraries is very easy.

### Example: Integrating an ORM (Doctrine)

Install Doctrine ORM:

```bash
composer require doctrine/orm
```

#### Example Model

```php
<?php
namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;

#[ORM\Entity]
#[ORM\Table(name: "data_items")]
class DataItemModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $name;

    #[ORM\Column(type: "string")]
    private string $value;

    private string $hello = "hi";

    private EntityManager $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getValue(): string { return $this->value; }
    public function setValue(string $value): void { $this->value = $value; }

    public function getHello(): string { return $this->hello; }
    public function setHello(string $hello): void { $this->hello = $hello; }

    public function getAllItems(): array
    {
        return $this->em->getRepository(self::class)->findAll();
    }

    public function createItem(string $name, string $value): int
    {
        $item = new self($this->em);
        $item->setName($name);
        $item->setValue($value);

        $this->em->persist($item);
        $this->em->flush();

        return $item->getId();
    }
}
```

#### Example Controller

```php
<?php
namespace App\Controllers;

use App\Models\DataItemModel;
use Framework\Attributes\Inject;
use Framework\Attributes\Controller;
use Framework\Core\View;
use Framework\Http\Get;
use Framework\Http\JsonResponse;
use Doctrine\ORM\EntityManager;

#[Controller('/data')]
class DataItemController
{
    #[Inject]
    private View $view;

    #[Inject]
    private DataItemModel $model;

    #[Get('/list')]
    #[JsonResponse]
    public function listItems(): array
    {
        $items = $this->model->getAllItems();
        return array_map(fn($item) => [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'value' => $item->getValue()
        ], $items);
    }

    #[Get('/create')]
    #[JsonResponse]
    public function createItem(): array
    {
        $id = $this->model->createItem('Sample Item', '123');
        return [
            'status' => 'success',
            'id' => $id
        ];
    }

    #[Get('/view')]
    public function renderView()
    {
        $items = $this->model->getAllItems();
        return $this->view->render("data/list.html", ['items' => $items], useTheme: false);
    }
}
```
