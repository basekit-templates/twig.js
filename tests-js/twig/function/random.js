var sinon = require("sinon");
describe("twig.functions.random", function () {

  before(function () {
    sinon.stub(Math, "random").returns(0.4);
  });

  after(function () {
    Math.random.restore();
  });

  it("chooses an element from an array", function () {
    twig.functions.random(["a", "b", "c"]).should.equal("b");
  });

  it("chooses a character from a string", function () {
    twig.functions.random("abc").should.equal("b");
  });

  it("generates a number between 0 and n", function () {
    twig.functions.random(10).should.equal(4);
  });

  it("generates a number between 0 and 2147483647 if n is null", function () {
    twig.functions.random().should.equal(858993458);
  });

});
