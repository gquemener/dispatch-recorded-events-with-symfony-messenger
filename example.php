<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class FooCommand
{
}
class FooCommandHandler
{
    public function __invoke(FooCommand $command): array
    {
        echo "FooCommand has been dispatched!" . \PHP_EOL;
        return [new FooEvent()];
    }
}

class FooEvent
{
}
class FooEventHandler
{
    public function __invoke(FooEvent $event): void
    {
        echo "FooEvent has been dispatched!" . \PHP_EOL;
    }
}

class DispatchDomainEventMiddleware implements MiddlewareInterface
{
    public function __construct(private MessageBusInterface $eventBus)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(HandledStamp::class);
        // TODO: Check that $stamp result is a single event or a collection of events before dispatching them
        foreach ($stamp->getResult() as $event) {
            $this->eventBus->dispatch($event);
        }
        return $stack->next()->handle($envelope, $stack);
    }
}

$eventBus = new MessageBus([
  new HandleMessageMiddleware(
      new HandlersLocator([
        FooEvent::class => [new FooEventHandler()],
      ]),
      allowNoHandlers: true
  ),
]);
$commandBus = new MessageBus([
  new HandleMessageMiddleware(new HandlersLocator([
    FooCommand::class => [new FooCommandHandler()],
  ])),
  new DispatchDomainEventMiddleware($eventBus),
]);

$commandBus->dispatch(new FooCommand());
