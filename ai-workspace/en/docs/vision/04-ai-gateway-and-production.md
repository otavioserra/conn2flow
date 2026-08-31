# 04 — AI Gateway & Production Proof

## An emerging, vendor-agnostic AI Gateway

**Conn2Flow Nexus** is an early-stage microservice designed to sit between
the core platform and multiple AI providers: a FastAPI entrypoint accepts a
task, queues it on Kafka, a worker routes it through LiteLLM to whichever
model is configured (OpenAI, Claude, Gemini, Groq, and others), and a
delivery worker posts the result back to Conn2Flow over a webhook. The
point is decoupling — the core never depends on a single vendor's SDK or
uptime, and agent work becomes asynchronous and observable instead of a
blocking HTTP call. This service is still evolving and should be read as a
direction, not a finished product.

## Beyond the browser: a mobile agent architecture

**Conn2Flow's mobile companion app** is built around the same pattern
applied to a different surface: a full-stack agent architecture that
mirrors the core's RBAC dynamically and clones existing administrative web
modules (HTML, JS, Tailwind) into native screens, consuming the same
authentication endpoints the web admin panel uses. It is evidence that
"content as an API, governed the same way for humans and agents" is not
specific to the browser.

## Running in production today

This is not a lab exercise. Multiple live client projects — each a private
overlay on top of the same Conn2Flow core, following the same governance
model described in this vision — already run in production. The specific
projects are intentionally not named here; what matters for this document
is that the pattern has already survived contact with real, paying
deployments, not just the core repository's own test suite.
