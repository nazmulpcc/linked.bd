### Models
-- Do not use `fillable` or `guarded` properties, model guarding is disabled in this project.
-- When you create a migration, update the property documentation in the respective model's doc block.

### Development Guidelines
-- Always create and dispatch laravel event classes when important business event happens. For example, user registration is an important event, but Laravel already has an event for that, so we don't need to create one. But a product creation, user buying a subscription etc should have their own event classes.